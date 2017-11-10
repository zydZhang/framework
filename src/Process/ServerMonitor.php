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
use Swoole\Process;

class ServerMonitor extends Process
{
    /**
     * @var Server
     */
    private $server;

    public function __construct(Server $server, bool $redirectStdinStdout = false, bool $createPipe = true)
    {
        parent::__construct([$this, 'processhandler'], $redirectStdinStdout, $createPipe);
        $this->server = $server;
    }

    public function getServer()
    {
        return $this->server;
    }

    public function processhandler(self $serverMonitor): void
    {
        $this->server->setProcessName('monitor');
        $serverMonitor->useQueue(ftok($this->server->setting['pid_file'], 1));
        while ($message = $this->receive()) {
            switch ($message['msg']) {
                case 'status':
                    $this->send($this->server->stats());
                    break;
                case 'clist':
                    $allConnList = [];
                    foreach ($this->server->connections as $fd) {
                        $allConnList[$fd] = $this->server->getClientInfo($fd);
                    }
                    $this->send($allConnList);
                    break;
                case 'plist':
                    break;
                case 'quit':
                    break;
                case 'stop':
                    break;
                case 'reload':
                    if ($this->server->reload()) {
                        $this->send('reload ok');
                    }
            }
        }
    }

    /**
     * @return mixed
     */
    private function receive()
    {
        $rawMessage = $this->pop();
        $message = json_decode($rawMessage, true);
        // 抢到自己发送的数据
        if ($message['from'] == getmypid()) {
            $this->push($rawMessage);
            sleep(1);

            return $this->receive();
        } else {
            return $message;
        }
    }

    /**
     * @param $msg
     */
    private function send($msg): void
    {
        $message = json_encode(
            [
                'from' => getmypid(),
                'msg'  => $msg,
            ]
        );
        $this->push($message);
    }
}
