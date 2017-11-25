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

use Eelly\Error\Handler as ErrorHandler;
use Eelly\Exception\RequestException;
use Eelly\Http\SwoolePhalconRequest;
use ErrorException;
use Exception;
use League\OAuth2\Server\Exception\OAuthServerException;
use Swoole\Server;
use swoole_http_request as SwooleHttpRequest;
use swoole_http_response as SwooleHttpResponse;

class HttpServerListener
{
    public function onStart(Server $server): void
    {
        $server->setProcessName('server');
    }

    public function onShutdown(): void
    {
    }

    public function onWorkerStart(Server $server, int $workerId): void
    {
        chdir(APP['root_path']);
        $processName = $workerId >= $server->setting['worker_num'] ? 'task#'.$workerId : 'event#'.$workerId;
        $server->setProcessName($processName);
        // 清除apc或op缓存
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    public function onWorkerStop(): void
    {
    }

    public function onRequest(SwooleHttpRequest $swooleHttpRequest, SwooleHttpResponse $swooleHttpResponse): void
    {
        if ('/favicon.ico' == $swooleHttpRequest->server['request_uri']) {
            $swooleHttpResponse->status(404);
            $swooleHttpResponse->end();

            return;
        }

        $swooleHttpResponse->end('hello');

        return;

        /* @var SwoolePhalconRequest  $phalconHttpRequest */
        $phalconHttpRequest = $this->di->get('request');
        $phalconHttpRequest->initialWithSwooleHttpRequest($swooleHttpRequest);
        $this->server->setSwooleHttpResponse($swooleHttpResponse);

        try {
            /* @var \Phalcon\Http\Response $response */
            $response = $this->application->handle();
            $response->setStatusCode(200);
        } catch (LogicException $e) {
            $response = $this->response->setHeader('returnType', get_class($e));
            $content = ['error' => $e->getMessage(), 'returnType' => get_class($e)];
            $content['context'] = $e->getContext();
            $response = $response->setJsonContent($content);
        } catch (RequestException $e) {
            $response = $e->getResponse();
        } catch (OAuthServerException $e) {
            $response = $this->response->setStatusCode($e->getHttpStatusCode());
            // TODO RFC 6749, section 5.2 Add "WWW-Authenticate" header
            $response->setJsonContent([
                'error'   => $e->getErrorType(),
                'message' => $e->getMessage(),
                'hint'    => $e->getHint(),
            ]);
        } catch (ErrorException | Exception $e) {
            $this->getDI()->getShared(ErrorHandler::class)->handleException($e);
            $response = $this->response;
        }
        $content = $response->getContent();
        $headers = $response->getHeaders();
        $swooleHttpResponse->status($response->getStatusCode());
        foreach ($headers->toArray() as $key => $value) {
            $swooleHttpResponse->header($key, (string) $value);
        }
        $swooleHttpResponse->end($content);
        if ($this->output->isDebug()) {
            $info = sprintf(
                '%s - %s %d "%s %s" %d "%s"',
                $swooleHttpRequest->server['remote_addr'],
                formatTime($this->defaultTimezone),
                $swooleHttpResponse->fd,
                $swooleHttpRequest->server['request_method'],
                $swooleHttpRequest->server['request_uri'],
                $response->getStatusCode(),
                $swooleHttpRequest->header['user-agent']
            );
            $this->io->writeln($info);
        }
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

    public function onTask(Server $server, int $taskId, int $workId, $data)
    {
        if (isset($data['class']) && method_exists($data['class'], $data['method'])) {
            return call_user_func_array([new $data['class'](), $data['method']], $data['params']);
        }
    }

    public function onFinish(Server $server, int $taskId, $data): void
    {
    }

    public function onPipeMessage(Server $server, int $workId, string $message): void
    {
    }

    public function onWorkerError(Server $server, int $workerId, int $workerPid, int $exitCode, int $signal): void
    {
        // $server->shutdown();
    }

    public function onManagerStart(Server $server): void
    {
        $server->setProcessName('manager');
    }

    public function onManagerStop(): void
    {
    }
}
