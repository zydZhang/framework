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

use Eelly\Http\SwoolePhalconRequest;
use Eelly\Network\HttpServer;
use Swoole\Server;
use swoole_http_request as SwooleHttpRequest;
use swoole_http_response as SwooleHttpResponse;

class HttpServerListener
{
    /**
     * @var HttpServer
     */
    private $server;

    public function onStart(HttpServer $server): void
    {
        $server->setProcessName('server');
    }

    public function onShutdown(): void
    {
    }

    public function onWorkerStart(HttpServer $server, int $workerId): void
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
        // 注册路由
        $server->registerRouter();
        $this->server = $server;
    }

    public function onWorkerStop(): void
    {
    }

    public function onRequest(SwooleHttpRequest $swooleHttpRequest, SwooleHttpResponse $swooleHttpResponse)
    {
        if ('/favicon.ico' == $swooleHttpRequest->server['request_uri']) {
            $swooleHttpResponse->status(404);
            $swooleHttpResponse->end();

            return;
        }
        $di = $this->server->getDi();

        /* @var SwoolePhalconRequest  $phalconHttpRequest */
        $phalconHttpRequest = $di->get('request');
        $phalconHttpRequest->initialWithSwooleHttpRequest($swooleHttpRequest);

        /* @var \Eelly\Router\ServiceRouter $router */
        $router = $di->getShared('router');
        $router->handle();
        //dump($router->getMatchedRoute());
        $moduleName = $router->getModuleName();
        if (null === $moduleName) {
            /* @var \Phalcon\Mvc\Dispatcher $dispatcher */
            $dispatcher = $di->getShared('dispatcher');
            $dispatcher->setModuleName($router->getModuleName());
            $dispatcher->setNamespaceName($router->getNamespaceName());
            $dispatcher->setControllerName($router->getControllerName());
            $dispatcher->setActionName($router->getActionName());
            try {
                $dispatcher->setParams($router->getParams());
                $controller = $dispatcher->dispatch();
            } catch (\Exception $e) {
                return $swooleHttpResponse->end($e->getMessage());
            }
            $possibleResponse = $dispatcher->getReturnedValue();
            if ($possibleResponse instanceof \Phalcon\Mvc\View) {
                // doc
                $possibleResponse->start();
                $possibleResponse->render(
                    $dispatcher->getControllerName(),
                    $dispatcher->getActionName(),
                    $dispatcher->getParams()
                );
                $content = $possibleResponse->getContent();
                $possibleResponse->finish();

                return $swooleHttpResponse->end($content);
            } else {
                // system api
                return $swooleHttpResponse->end('system api');
            }
        } else {
            // service api
            return $swooleHttpResponse->end('service api');
        }
    }

    public function onPacket(): void
    {
    }

    public function onClose(HttpServer $server, int $fd, int $reactorId): void
    {
    }

    public function onBufferFull(): void
    {
    }

    public function onBufferEmpty(): void
    {
    }

    public function onTask(HttpServer $server, int $taskId, int $workId, $data)
    {
        if (isset($data['class']) && method_exists($data['class'], $data['method'])) {
            return call_user_func_array([new $data['class'](), $data['method']], $data['params']);
        }
    }

    public function onFinish(HttpServer $server, int $taskId, $data): void
    {
    }

    public function onPipeMessage(HttpServer $server, int $workId, string $message): void
    {
    }

    public function onWorkerError(HttpServer $server, int $workerId, int $workerPid, int $exitCode, int $signal): void
    {
        $server->shutdown();
    }

    public function onManagerStart(HttpServer $server): void
    {
        $server->setProcessName('manager');
    }

    public function onManagerStop(): void
    {
    }
}
