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
use Eelly\Console\Command\HttpServerCommand;
use Eelly\Di\Injectable;
use Eelly\Di\SwooleDi;
use Phalcon\Config;

/**
 * @author hehui<hehui@eelly.net>
 */
class SwooleApplication extends Injectable
{
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

    /**
     * run.
     */
    public function run(): void
    {
        $consoleApplication = new ConsoleApplication(ApplicationConst::APP_NAME, ApplicationConst::APP_VERSION);
        $consoleApplication->add($this->di->getShared(HttpServerCommand::class));
        $consoleApplication->run();
    }
}
