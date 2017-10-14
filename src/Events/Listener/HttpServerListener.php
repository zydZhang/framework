<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eelly\Events\Listener;

use Eelly\Application\ApplicationConst;
use Eelly\Error\Handler as ErrorHandler;
use Eelly\Exception\LogicException;
use Eelly\Exception\RequestException;
use Eelly\Http\Server;
use Eelly\Http\SwoolePhalconRequest;
use Eelly\Http\Traits\RequestTrait;
use Eelly\Http\Traits\ResponseTrait;
use Eelly\Mvc\Application as MvcApplication;
use ErrorException;
use League\OAuth2\Server\Exception\OAuthServerException;
use Phalcon\Di;
use swoole_http_request as SwooleHttpRequest;
use swoole_http_response as SwooleHttpResponse;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class HttpServerListener extends AbstractListener
{
    use RequestTrait;
    use ResponseTrait;
    private $input;
    private $output;
    private $io;
    private $defaultTimezone;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);
        $this->defaultTimezone = $this->config->defaultTimezone;
    }

    public function onStart(Server $server): void
    {
        $info = sprintf('%s start on <info>%s:%d</info>', formatTime($this->defaultTimezone), $server->host, $server->port);
        $this->io->writeln($info);
        $masterPid = $server->master_pid;
        $managerPid = $server->manager_pid;
        \Swoole\Async::writeFile('var/master.pid', (string) $masterPid);
        \Swoole\Async::writeFile('var/manager.pid', (string) $managerPid);
        $this->io->table(['name', 'pid', 'file'], [
            ['master', $masterPid, 'var/master.pid'],
            ['manager', $managerPid, 'var/manager.pid'],
        ]);
    }

    public function onShutdown(): void
    {
    }

    public function onWorkerStart(Server $server, int $workerId): void
    {
        $module = $this->input->getArgument('module');
        if ($workerId >= $server->setting['worker_num']) {
            $processName = "php httpserver task worker #{$workerId}";
            swoole_set_process_name($processName);
        } else {
            $processName = "php httpserver {$module} event worker #{$workerId}";
            swoole_set_process_name($processName);
        }
        $info = sprintf('%s <info>worker start</info> %s', formatTime($this->defaultTimezone), $processName);
        $this->io->writeln($info);

        $di = $this->getDI();
        $di->setShared('application', new MvcApplication($di));
        $config = $this->config;
        ApplicationConst::$env = $config->env;
        date_default_timezone_set($config->defaultTimezone);
        $errorHandler = $di->getShared(ErrorHandler::class);
        $errorHandler->register();
        $this->initEventsManager();
        foreach ($config->appBundles as $bundle) {
            $di->getShared($bundle->class, $bundle->params)->register();
        }
        $modules = [
            $module => [
                'className' => ucfirst($module).'\\Module',
                'path'      => 'src/'.ucfirst($module).'/Module.php',
            ],
        ];
        $this->application->registerModules($modules);
    }

    public function onWorkerStop(): void
    {
    }

    public function onRequest(SwooleHttpRequest $swooleHttpRequest, SwooleHttpResponse $swooleHttpResponse): void
    {
        if ($swooleHttpRequest->server['request_uri'] == '/favicon.ico') {
            $swooleHttpResponse->header('Content-Type', 'image/x-icon');
            $swooleHttpResponse->sendfile('public/favicon.ico');

            return;
        }
        /* @var SwoolePhalconRequest  $phalconHttpRequest */
        $phalconHttpRequest = $this->di->get('request');
        $phalconHttpRequest->initialWithSwooleHttpRequest($swooleHttpRequest);
        // TODO
        $response = $this->application->handle();

        return;

        try {
            $response = $this->application->handle();
        } catch (LogicException $e) {
            $response->setHeader('returnType', get_class($e));
            $content = ['error' => $e->getMessage(), 'returnType' => get_class($e)];
            $content['context'] = $e->getContext();
            $response->setJsonContent($content);
        } catch (RequestException $e) {
            // ...
        } catch (OAuthServerException $e) {
            // TODO RFC 6749, section 5.2 Add "WWW-Authenticate" header
            $response->setJsonContent([
                'error'   => $e->getErrorType(),
                'message' => $e->getMessage(),
                'hint'    => $e->getHint(),
            ]);
        } catch (ErrorException $e) {
            //...
        }
        $content = $response->getContent();
        $swooleHttpResponse->end($content);
        $info = sprintf(
            '[%s] %d "%s %s %d"',
            formatTime($this->defaultTimezone),
            $swooleHttpResponse->fd,
            $swooleHttpRequest->server['request_method'],
            $swooleHttpRequest->server['request_uri'],
            $response->getStatusCode()
        );
        $this->io->writeln($info);
    }

    public function onPacket(): void
    {
    }

    public function onClose(Server $server, int $fd, int $reactorId): void
    {
    }

    public function onBufferFull(): void
    {
    }

    public function onBufferEmpty(): void
    {
    }

    public function onTask(): void
    {
    }

    public function onFinish(): void
    {
    }

    public function onPipeMessage(): void
    {
    }

    public function onWorkerError(): void
    {
    }

    public function onManagerStart(): void
    {
    }

    public function onManagerStop(): void
    {
    }

    private function initEventsManager()
    {
        /**
         * @var \Phalcon\Events\Manager
         */
        $eventsManager = $this->eventsManager;
        $eventsManager->attach('di:afterServiceResolve', function (\Phalcon\Events\Event $event, \Phalcon\Di $di, array $service): void {
            if ($service['instance'] instanceof \Phalcon\Events\EventsAwareInterface) {
                $service['instance']->setEventsManager($di->getEventsManager());
            }
            if (method_exists($service['instance'], 'afterServiceResolve')) {
                $service['instance']->afterServiceResolve();
            }
        });
        $eventsManager->attach('dispatch:afterDispatchLoop', function (\Phalcon\Events\Event $event, \Phalcon\Mvc\Dispatcher $dispatcher): void {
            $returnedValue = $dispatcher->getReturnedValue();
            if (is_object($returnedValue)) {
                $this->response->setHeader('returnType', get_class($returnedValue));
                if ($returnedValue instanceof \JsonSerializable) {
                    $this->response->setJsonContent(['data' => $returnedValue, 'returnType' => get_class($returnedValue)]);
                }
            } elseif (is_array($returnedValue)) {
                $this->response->setHeader('returnType', 'array');
                $this->response->setJsonContent(['data' => $returnedValue, 'returnType' => 'array']);
            } elseif (is_scalar($returnedValue)) {
                $this->response->setHeader('returnType', gettype($returnedValue));
                $this->response->setJsonContent(
                    ['data' => $returnedValue, 'returnType' => gettype($returnedValue)]
                );
                if (is_string($returnedValue)) {
                    $dispatcher->setReturnedValue($this->response->getContent());
                }
            }
        });
        $this->application->setEventsManager($eventsManager);
        $this->di->setInternalEventsManager($eventsManager);

        return $this;
    }
}
