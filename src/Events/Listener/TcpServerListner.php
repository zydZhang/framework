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

use Eelly\Network\TcpServer;

/**
 * Class TcpServerListner.
 */
class TcpServerListner
{
    public function onStart(TcpServer $server): void
    {
        $server->setProcessName('server');
        $info = sprintf('%s tcp server was started and listening on <info>%d</info>', $server->getModule(), $server->port);
        $server->writeln($info);
    }

    public function onShutdown(): void
    {
    }

    public function onWorkerStart(TcpServer $server, int $workerId): void
    {
        chdir(ROOT_PATH);
        $processName = $workerId >= $server->setting['worker_num'] ? 'task#'.$workerId : 'event#'.$workerId;
        $server->setProcessName($processName);
        // 清除apc或op缓存
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        $server->registerModule();
        $server->registerRouter();
    }

    public function onWorkerStop(): void
    {
    }

    public function onConnect(): void
    {
    }

    public function onReceive(TcpServer $server, int $fd, int $reactorId, string $data): void
    {
        $data = \GuzzleHttp\json_decode($data, true);
        $uri = $data['uri'];
        $actionMethod = $data['method'];
        $params = $data['params'];
        $di = $server->getDi();
        /* @var \Eelly\Router\ServiceRouter $router */
        $router = $di->getShared('router');
        $router->handle($uri);
        $router->setParams($params);
        /* @var \Phalcon\Mvc\Dispatcher $dispatcher */
        $dispatcher = $di->getShared('dispatcher');
        $dispatcher->setModuleName($router->getModuleName());
        $dispatcher->setNamespaceName($router->getNamespaceName());
        $dispatcher->setControllerName($router->getControllerName());
        $dispatcher->setActionName($router->getActionName());
        $controller = $dispatcher->dispatch();
        $possibleResponse = $dispatcher->getReturnedValue();
        $server->send($fd, json_encode(['data' => $possibleResponse]));
    }

    public function onPacket(): void
    {
    }

    public function onClose(): void
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
}
