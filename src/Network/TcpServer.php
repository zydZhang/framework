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

use Eelly\Client\TcpClient;
use Eelly\Events\Listener\TcpServerListner;
use Eelly\Exception\RequestException;
use Phalcon\DiInterface;
use Swoole\Atomic\Long;
use Swoole\Lock;
use Swoole\Server;
use Swoole\Table;
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

    /**
     * max module map count.
     */
    private const MAX_MODULE_MAP_COUNT = 50;

    /**
     * @var TcpServerListner
     */
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

    /**
     * @var Lock
     */
    private $lock;

    /**
     * @var Long
     */
    private $requestCount;

    /**
     * @var Table
     */
    private $moduleMap;

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
        $this->requestCount = new Long();
        $this->moduleMap = $this->createModuleMap();
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
     * @param int    $option
     */
    public function writeln(string $string, $option = 0)
    {
        $info = sprintf('[%s %d] %s', formatTime(), getmypid(), $string);
        $this->lock->lock();
        $this->output->writeln($info, $option);
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
     * register remote module.
     *
     * @param string $module
     * @param string $ip
     * @param int    $port
     */
    public function registerRemoteModule(string $module, string $ip, int $port): void
    {
        if ($module == $this->module) {
            return;
        }
        $record = $this->moduleMap->get($module);
        $created = false == $record ? time() : $record['created'];
        $this->moduleMap->set($module, ['ip' => $ip, 'port' => $port, 'created' => $created, 'updated' => time()]);
        $this->writeln(sprintf('register module(%s) %s:%d', $module, $ip, $port), OutputInterface::VERBOSITY_DEBUG);
    }

    /**
     * register router.
     */
    public function registerRouter(): void
    {
        /* @var \Phalcon\Mvc\Router $router */
        $router = $this->di->getShared('router');
        $moduleName = $this->module;
        $namespace = ucfirst($moduleName).'\\Logic';
        $router->add('/'.$moduleName.'/:controller/:action', [
            'namespace'  => $namespace,
            'module'     => $moduleName,
            'controller' => 1,
            'action'     => 2,
        ]);
    }

    /**
     * @param string $moduleName
     *
     * @return TcpClient
     */
    public function getModuleClient(string $moduleName)
    {
        $module = $this->moduleMap->get($moduleName);
        if (false === $module) {
            throw new RequestException(404, 'Module not found', $this->di->getShared('request'), $this->di->getShared('response'));
        }
        static $mdduleClientMap = [];
        if (isset($mdduleClientMap[$moduleName])) {
            if ($mdduleClientMap[$moduleName]['ip'] == $module['ip']
                && $mdduleClientMap[$moduleName]['port'] == $module['port']) {
                if (!$mdduleClientMap[$moduleName]['client']->isConnected()) {
                    $mdduleClientMap[$moduleName]['client']->connect($module['ip'], $module['port']);
                }

                return $mdduleClientMap[$moduleName]['client'];
            } else {
                // force close
                $mdduleClientMap[$moduleName]['client']->close(true);
                unset($mdduleClientMap[$moduleName]);
            }
        }
        $client = new TcpClient(SWOOLE_TCP | SWOOLE_KEEP);
        $client->connect($module['ip'], $module['port']);
        $mdduleClientMap[$moduleName] = [
            'ip'     => $module['ip'],
            'port'   => $module['port'],
            'client' => $client,
        ];

        return $client;
    }

    /**
     * @param int    $fd
     * @param string $data
     * @param int    $fromId
     *
     * @return bool
     */
    public function send($fd, $data, $fromId = 0): bool
    {
        return parent::send($fd, $data."\r\n", $fromId);
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

    /**
     * @return Long
     */
    public function getRequestCount()
    {
        return $this->requestCount;
    }

    /**
     * @return Table
     */
    private function createModuleMap()
    {
        $moduleMap = new Table(self::MAX_MODULE_MAP_COUNT);
        $moduleMap->column('ip', Table::TYPE_STRING, 15);
        $moduleMap->column('port', Table::TYPE_INT);
        $moduleMap->column('created', Table::TYPE_INT);
        $moduleMap->column('updated', Table::TYPE_INT);
        $moduleMap->create();

        return $moduleMap;
    }
}
