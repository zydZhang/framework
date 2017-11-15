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

namespace Eelly\Network;

use Eelly\Events\Listener\HttpServerListener;
use Swoole\Http\Server as SwooleHttpServer;
use Swoole\Lock;
use Swoole\Table;
use swoole_http_response as SwooleHttpResponse;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class Server.
 */
class HttpServer extends SwooleHttpServer
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

    /**
     * 基础进程数.
     *
     * @var int
     */
    private const BASE_PROCESS_NUM = 3;

    /**
     * 进程ID表.
     *
     * @var Table
     */
    private $processIdTable;

    /**
     * @var SwooleHttpResponse
     */
    private $swooleHttpResponse;

    /**
     * 服务名.
     *
     * @var string
     */
    private $module;

    private $io;

    private $lock;

    public function __construct(
        string $host,
        int $port,
        HttpServerListener $httpServerListener,
        array $options,
        InputInterface $input,
        OutputInterface $output,
        int $mode = SWOOLE_PROCESS,
        int $sockType = SWOOLE_SOCK_TCP)
    {
        parent::__construct($host, $port, $mode, $sockType);
        $this->set($options);
        $this->processIdTable = new Table(3 + $this->setting['worker_num'] + $this->setting['task_worker_num']);
        $this->processIdTable->column('id', Table::TYPE_INT);
        $this->processIdTable->column('created', Table::TYPE_INT);
        $this->processIdTable->create();
        $this->io = new SymfonyStyle($input, $output);
        $this->lock = new Lock(SWOOLE_MUTEX);
        $this->module = $options['module'];
        $httpServerListener->setServer($this);
        foreach (self::EVENTS as $event) {
            $this->on($event, [$httpServerListener, 'on'.$event]);
        }
    }

    /**
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * @return SwooleHttpResponse
     */
    public function getSwooleHttpResponse(): SwooleHttpResponse
    {
        return $this->swooleHttpResponse;
    }

    /**
     * @param SwooleHttpResponse $swooleHttpResponse
     */
    public function setSwooleHttpResponse(SwooleHttpResponse $swooleHttpResponse): void
    {
        $this->swooleHttpResponse = $swooleHttpResponse;
    }

    public function setProcessName(string $name): void
    {
        $processName = $this->module.'_'.$name;
        swoole_set_process_name($processName);
        $pid = getmypid();
        $this->processIdTable->set($processName, ['id' => $pid, 'created' => time()]);
        $info = sprintf('%s "%s" %d', formatTime(), $processName, $pid);
        $this->lock->lock();
        $this->io->writeln($info);
        $this->lock->unlock();
    }

    public function getProcessInfo(string $name): array
    {
        return $this->processIdTable->get($this->module.'_'.$name);
    }

    public function getAllProcessInfo(): array
    {
        $process = [];
        foreach ($this->processIdTable as $key => $row) {
            $process[$key] = $row;
        }

        return $process;
    }
}
