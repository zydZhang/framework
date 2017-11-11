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

namespace Eelly\Process;

use Eelly\Http\Server;

/**
 * 服务器监控进程.
 */
class ServerMonitor extends Process
{
    /**
     * @var Server
     */
    private $server;

    public function __construct(Server $server = null, bool $redirectStdinStdout = false, int $createPipe = 2)
    {
        parent::__construct([$this, 'processhandler'], $redirectStdinStdout, $createPipe);
        $this->server = $server;
    }

    public function getServer()
    {
        return $this->server;
    }

    public function processhandler(ServerMonitor $serverMonitor): void
    {
        $this->reCreateQueue();
        $this->server->setProcessName('monitor');
        // 处理客户端发送的消息
        while ($message = $this->receive('server', 'client')) {
            switch ($message['msg']) {
                case 'stats':
                    $this->sendMessageToClient($this->server->stats());
                    break;
                case 'clist':
                    $allConnList = [];
                    foreach ($this->server->connections as $fd) {
                        $allConnList[$fd] = $this->server->getClientInfo($fd);
                    }
                    $this->sendMessageToClient($allConnList);
                    break;
                case 'plist':
                    $this->sendMessageToClient($this->server->getAllProcessInfo());
                    break;
                case 'shutdown':
                    $this->sendMessageToClient($this->server->shutdown());
                    break;
                case 'reload':
                    $this->sendMessageToClient($this->server->reload());
                    break;
                default:
                    $this->sendMessageToClient(false);
            }
        }
    }

    private function sendMessageToClient($msg)
    {
        return $this->send('server', 'client', $msg);
    }
}
