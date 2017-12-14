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
use Phalcon\DiInterface;
use Swoole\Http\Server as SwooleHttpServer;
use Swoole\Lock;
use Swoole\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Server.
 */
class HttpServer extends SwooleHttpServer
{
    /**
     * event list.
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
     * max module map count.
     */
    private const MAX_MODULE_MAP_COUNT = 50;

    /**
     * @var OutputInterface
     */
    private $output;

    private $listner;

    /**
     * @var DiInterface
     */
    private $di;

    /**
     * @var Lock
     */
    private $lock;

    /**
     * @var Table
     */
    private $moduleMap;

    public function __construct(string $host, int $port)
    {
        parent::__construct($host, $port);
        $this->listner = new HttpServerListener();
        $this->lock = new Lock(SWOOLE_MUTEX);
        $this->moduleMap = $this->createModuleMap();
        foreach (self::EVENTS as $event) {
            $this->on($event, [$this->listner, 'on'.$event]);
        }
    }

    public function registerRouter(): void
    {
        /* @var \Phalcon\Mvc\Router $router */
        $router = $this->di->getShared('router');
        // system
        $router->addPost('/_/:controller/:action', [
            'namespace'  => 'Eelly\\Controller',
            'controller' => 1,
            'action'     => 2,
        ]);
        // doc
        foreach ($this->di->getShared('config')->appBundles as $bundle) {
            $this->di->getShared($bundle)
                ->registerService()
                ->registerRouter();
        }
        // service api
        foreach ($this->di->getShared('config')->moduleList as $moduleName) {
            $namespace = ucfirst($moduleName);
            $router->addPost('/'.$moduleName.'/:controller/:action', [
                'namespace'  => $namespace,
                'module'     => $moduleName,
                'controller' => 1,
                'action'     => 2,
            ]);
        }
    }

    public function setProcessName(string $name): void
    {
        $processName = 'httpserver_'.$name;
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
     *
     * @param string $module
     * @param string $ip
     * @param int    $port
     */
    public function registerModule(string $module, string $ip, int $port): void
    {
        $record = $this->moduleMap->get($module);
        $created = false == $record ? time() : $record['created'];
        $this->moduleMap->set($module, ['ip' => $ip, 'port' => $port, 'created' => $created, 'updated' => time()]);
        $this->writeln(sprintf('register module(%s) %s:%d', $module, $ip, $port));
    }

    /**
     * @return array
     */
    public function getModuleMap()
    {
        $moduleMap = [];
        foreach ($this->moduleMap as $module => $value) {
            $moduleMap[$module] = $value;
        }

        return $moduleMap;
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
