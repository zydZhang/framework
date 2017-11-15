<?php
/**
 * Created by PhpStorm.
 * User: heui
 * Date: 2017/11/14
 * Time: 17:00
 */

namespace Eelly\Network;


use Eelly\Events\Listener\TcpServerListner;
use Swoole\Server;

class TcpServer extends Server
{
    /**
     * 事件列表.
     *
     * @var array
     */
    private const EVENTS = [
        'Start',
        'Shutdown',
        'WorkerStart',
        'WorkerStop',
        'Connect',
        'Receive',
        'Packet',
        'Close',
        'BufferFull',
        'BufferEmpty',
        'Task',
        'Finish',
        'PipeMessage',
        'WorkerError',
        'ManagerStart',
        'ManagerStop',
    ];

    private $listner;
    /**
     * TcpServer constructor.
     *
     * @param string $host
     * @param int $port
     * @param int $mode
     * @param int $sockType
     */
    public function __construct(string $host, int $port = 0, int $mode = SWOOLE_PROCESS, int $sockType = SWOOLE_SOCK_TCP)
    {
        parent::__construct($host, $port, $mode, $sockType);
        $this->listner = new TcpServerListner();
        foreach (self::EVENTS as $event) {
            $this->on($event, [$this->listner, 'on'.$event]);
        }
    }
}