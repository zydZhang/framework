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

use Composer\Autoload\ClassLoader;
use Eelly\Console\Application;
use Eelly\Console\Command\FlushCacheCommand;
use Eelly\Console\Command\HttpServerCommand;
use Eelly\Console\Command\QueueConsumerCommand;
use Eelly\Console\Command\TcpServerCommand;
use Eelly\Di\ConsoleDi;
use Phalcon\Config;
use Phalcon\Di;

/**
 * @author hehui<hehui@eelly.net>
 */
class ConsoleApplication
{
    /**
     * @var Application
     */
    private $application;

    private $di;

    /**
     * ConsoleApplication constructor.
     *
     * @param ClassLoader $classLoader
     * @param array       $config
     */
    public function __construct(ClassLoader $classLoader, array $config)
    {
        $this->di = new ConsoleDi();
        $this->di->setShared('loader', $classLoader);
        $config = new Config($config);
        $this->di->setShared('config', $config);
        ApplicationConst::$env = $config->env;
        date_default_timezone_set($config->defaultTimezone);
        $this->application = $this->di->getShared(Application::class, [ApplicationConst::APP_NAME, ApplicationConst::APP_VERSION]);
        $this->application->setDispatcher($this->di->get('eventDispatcher'));
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
        $this->handle()->run();
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
