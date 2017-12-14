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

use Eelly\Events\Listener\TcpServerListner;
use Phalcon\DiInterface;
use Swoole\Lock;
use Swoole\Server;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var string
     */
    private $module;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var DiInterface
     */
    private $di;

    private $lock;

    /**
     * TcpServer constructor.
     *
     * @param string $host
     * @param int    $port
     * @param int    $mode
     * @param int    $sockType
     */
    public function __construct(string $host, int $port = 0, int $mode = SWOOLE_PROCESS, int $sockType = SWOOLE_SOCK_TCP)
    {
        parent::__construct($host, $port, $mode, $sockType);
        $this->listner = new TcpServerListner();
        $this->lock = new Lock(SWOOLE_MUTEX);
        foreach (self::EVENTS as $event) {
            $this->on($event, [$this->listner, 'on'.$event]);
        }
    }

    public function setProcessName(string $name): void
    {
        $processName = $this->module.'_'.$name;
        swoole_set_process_name($processName);
        $this->writeln($processName);
    }

    /**
     * @param string $string
     */
    public function writeln(string $string)
    {
        $info = sprintf('[%s %d] %s', formatTime(), getmypid(), $string);
        $this->lock->lock();
        $this->output->writeln($info);
        $this->lock->unlock();
    }

    /**
     * register module.
     */
    public function registerModule(): void
    {
        $module = ucfirst($this->module).'\\Module';
        /* @var \Eelly\Mvc\AbstractModule $moduleInstance */
        $moduleInstance = $this->di->getShared($module);
        $moduleInstance->registerAutoloaders($this->di);
        $moduleInstance->registerServices($this->di);
    }

    /**
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * @param string $module
     */
    public function setModule(string $module): void
    {
        $this->module = $module;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * @return DiInterface
     */
    public function getDi(): DiInterface
    {
        return $this->di;
    }

    /**
     * @param DiInterface $di
     */
    public function setDi(DiInterface $di): void
    {
        $this->di = $di;
    }
}
