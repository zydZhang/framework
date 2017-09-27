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

namespace Eelly\Http;

use Eelly\Events\Listener\HttpServerListener;
use Swoole\Http\Server as HttpServer;

/**
 * Class Server.
 */
class Server extends HttpServer
{
    /**
     * 事件列表.
     *
     * @var array
     */
    private $events = [
        'Start',
        'Shutdown',
        'WorkerStart',
        'WorkerStop',
        'Request',
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

    public function __construct(
        string $host,
        int $port,
        HttpServerListener $httpServerListener,
        array $options,
        int $mode = SWOOLE_PROCESS,
        int $sockType = SWOOLE_SOCK_TCP)
    {
        parent::__construct($host, $port, $mode, $sockType);
        $this->set($options);
        foreach ($this->events as $event) {
            $this->on($event, [$httpServerListener, 'on'.$event]);
        }
    }
}
