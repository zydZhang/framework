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
use Eelly\Di\TcpServerDi;
use Eelly\Network\TcpServer;
use Phalcon\Di;

class TcpServerListner
{
    public function onStart(TcpServer $server): void
    {
        $server->setProcessName('server');
    }

    public function onShutdown(): void
    {
    }

    public function onWorkerStart(TcpServer $server, int $workerId): void
    {
        chdir(APP['root_path']);
        $processName = $workerId >= $server->setting['worker_num'] ? 'task#'.$workerId : 'event#'.$workerId;
        $server->setProcessName($processName);
        $module = $server->getModule();
        // 清除apc或op缓存
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        // initialize di
        $di = new TcpServerDi(ApplicationConst::$env, $module);
        $server->setDi($di);
        // initialize module
        $moduleClass = ucfirst($module).'\\Module';
        /* @var \Eelly\Mvc\AbstractModule $moduleObject */
        $moduleObject = $di->get($moduleClass);
        $moduleObject->registerAutoloaders($di);
        $moduleObject->registerServices($di);
    }

    public function onWorkerStop(): void
    {
    }

    public function onConnect(): void
    {
    }

    public function onReceive(TcpServer $server, int $fd, int $reactorId, string $data): void
    {
        $data = json_decode($data, true);
        $handlerClass = $data['class'];
        $actionMethod = $data['method'];
        $params = $data['params'];
        $handler = $server->getDi()->getShared($handlerClass);

        $data = call_user_func_array([$handler, $actionMethod], $params);
        $server->send($fd, json_encode([
            'data' => $data,
        ]));
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
