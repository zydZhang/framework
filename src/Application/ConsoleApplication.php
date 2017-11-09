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

use Eelly\Console\Application;
use Eelly\Console\Command\FlushCacheCommand;
use Eelly\Di\ConsoleDi;
use Eelly\Di\Injectable;
use Eelly\Error\Handler as ErrorHandler;
use Phalcon\Config;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * console application.
 *
 * @property \Eelly\Console\Application $application
 *
 * @author hehui<hehui@eelly.net>
 *
 * @deprecated
 */
class ConsoleApplication extends Injectable
{
    /**
     * ConsoleApplication constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $di = new ConsoleDi();
        $di->setShared('config', new Config($config));
        $this->setDI($di);
    }

    public function initialize()
    {
        $di = $this->getDI();
        $di->setShared('application', function () {
            $applications = new Application(ApplicationConst::APP_NAME, ApplicationConst::APP_VERSION);
            $applications->setDispatcher($this->get('eventDispatcher'));

            return $applications;
        });
        $config = $di->getConfig();
        ApplicationConst::$env = $config->env;
        date_default_timezone_set($config->defaultTimezone);

        $errorHandler = $di->getShared(ErrorHandler::class);
        $errorHandler->register();

        $this->application->addCommands([
            $di->get(FlushCacheCommand::class),
        ]);

        return $this;
    }

    public function handle()
    {
        $this->application->registerModules($this->config->modules->toArray());

        return $this->application;
    }

    public function run(): void
    {
        $this->initialize()->handle()->run();
    }
}
