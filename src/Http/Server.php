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

use Eelly\Di\InjectionAwareInterface;
use Eelly\Di\Traits\InjectableTrait;
use Eelly\Events\Listener\HttpServerListener;
use Phalcon\Events\EventsAwareInterface;
use Swoole\Http\Server as HttpServer;

/**
 * Class Server.
 */
class Server extends HttpServer implements InjectionAwareInterface, EventsAwareInterface
{
    use InjectableTrait;

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

    public function initialize(): void
    {
        $listener = $this->di->getShared(HttpServerListener::class);
        $this->set([
            'document_root' => 'var/assets',
            'enable_static_handler' => true,
            'http_parse_post' => true,
            'upload_tmp_dir' => '/tmp'
        ]);
        foreach ($this->events as $event) {
            $this->on($event, [$listener, 'on'.$event]);
        }
    }
}
