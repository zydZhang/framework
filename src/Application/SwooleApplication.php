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

namespace Eelly\Application;

use Eelly\Console\Application as ConsoleApplication;
use Eelly\Console\Command\FlushCacheCommand;
use Eelly\Console\Command\HttpServerCommand;
use Eelly\Console\Command\QueueConsumerCommand;
use Eelly\Console\Command\TcpServerCommand;
use Eelly\Di\Injectable;
use Eelly\Di\SwooleDi;
use Phalcon\Config;

/**
 * @author hehui<hehui@eelly.net>
 */
class SwooleApplication extends Injectable
{
    /**
     * @var ConsoleApplication
     */
    private $application;

    /**
     * ServiceApplication constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $di = new SwooleDi();
        $di->setShared('config', new Config($config));
        $this->setDI($di);
    }

    public function initialize()
    {
        ApplicationConst::$env = $this->config->env;
        date_default_timezone_set($this->config->defaultTimezone);
        $consoleApplication = new ConsoleApplication(ApplicationConst::APP_NAME, ApplicationConst::APP_VERSION);
        $consoleApplication->setDI($this->di);
        $consoleApplication->setDispatcher($this->di->get('eventDispatcher'));
        $this->application = $consoleApplication;

        return $this;
    }

    public function handle()
    {
        // 添加基础命令
        $this->addBaseCommands([
            FlushCacheCommand::class,
            HttpServerCommand::class,
            QueueConsumerCommand::class,
            TcpServerCommand::class,
        ]);
        // 添加各模块的命令
        $this->application->addModulesCommands();

        return $this->application;
    }

    /**
     * run.
     */
    public function run(): void
    {
        $this->initialize()->handle()->run();
    }

    /**
     * @param array $commands
     */
    private function addBaseCommands(array $commands): void
    {
        foreach ($commands as $command) {
            $this->application->add($this->di->getShared($command));
        }
    }
}
