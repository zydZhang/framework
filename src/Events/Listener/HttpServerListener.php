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
use Eelly\Http\Response;
use Eelly\Http\Server;
use Eelly\Http\ServiceRequest;
use Eelly\Http\ServiceResponse;
use Eelly\Http\Traits\RequestTrait;
use Eelly\Http\Traits\ResponseTrait;
use Eelly\Mvc\Application as MvcApplication;
use ErrorException;
use League\OAuth2\Server\Exception\OAuthServerException;
use Phalcon\Di;
use swoole_http_request as HttpRequest;
use swoole_http_response as HttpResponse;
use Symfony\Component\Console\Style\SymfonyStyle;

class HttpServerListener extends AbstractListener
{
    use RequestTrait;
    use ResponseTrait;
    private $io;

    public function __construct(SymfonyStyle $io)
    {
        $this->io = $io;
    }

    public function onStart(Server $server): void
    {
        $info = $this->sprintf('start on %s:%d', $server->host, $server->port);
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
        dump(__FUNCTION__);
    }

    public function onWorkerStart(Server $server, int $workerId): void
    {
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
        $this->application->useImplicitView(false);
        $this->application->registerModules($this->config->modules->toArray());
    }

    public function onWorkerStop(): void
    {
        dump(__FUNCTION__);
    }

    public function onRequest(HttpRequest $httpRequest, HttpResponse $httpResponse): void
    {
        dump(__FUNCTION__.$httpRequest->fd);
        if ($httpRequest->server['request_uri'] == '/favicon.ico') {
            $httpResponse->header('Content-Type', 'image/x-icon');
            $httpResponse->sendfile('public/favicon.ico');

            return;
        }

        $_SERVER['REQUEST_URI'] = $httpRequest->server['request_uri'];
        $request = new ServiceRequest();
        $this->di->set('request', $request);
        $response = new ServiceResponse();
        $this->di->set('response', $response);
        $this->convertSwooleRequestToPhalconRequest($httpRequest, $request);

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
            $response->setStatusCode($e->getHttpStatusCode());
            // TODO RFC 6749, section 5.2 Add "WWW-Authenticate" header
            $response->setJsonContent([
                'error'   => $e->getErrorType(),
                'message' => $e->getMessage(),
                'hint'    => $e->getHint(),
            ]);
        } catch (ErrorException $e) {
            //...
        }
        $this->convertPhalconResponseToSwooleResponse($response, $httpResponse);
        $content = $response->getContent();
        $httpResponse->end($content);
    }

    public function onPacket(): void
    {
    }

    public function onClose(Server $server, int $fd, int $reactorId): void
    {
        dump(__FUNCTION__.$fd.'_'.$reactorId);
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
        dump(__FUNCTION__);
    }

    public function onManagerStop(): void
    {
        dump(__FUNCTION__);
    }

    private function sprintf(string $format, ...$args)
    {
        $dateTime = \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)));
        $time = $dateTime->format('[Y-m-d H:i:s.u] ');

        array_unshift($args, $format);

        return $time.call_user_func_array('sprintf', $args);
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
