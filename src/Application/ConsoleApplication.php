<?php
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
use Eelly\Error\Handler as ErrorHandler;

/**
 * console application.
 *
 * @property \Eelly\Console\Application $application
 *
 * @author hehui<hehui@eelly.net>
 */
class ConsoleApplication extends AbstractApplication
{
    public function initial()
    {
        $di = $this->getDI();
        $di->setShared('application', new Application(self::APP_NAME, self::APP_VERSION));
        $config = $di->getConfig();
        self::$env = $config->env;
        date_default_timezone_set($config->defaultTimezone);

        $errorHandler = $di->getShared(ErrorHandler::class);
        $errorHandler->register();

        $this->initEventsManager();

        return $this;
    }

    public function handle()
    {
        $this->application->registerModules($this->config->modules->toArray());

        return $this->application;
    }

    public function run()
    {
        $this->initial()->handle()->run();
    }

    private function initEventsManager(): void
    {
        /**
         * @var \Phalcon\Events\Manager $eventsManager
         */
        $eventsManager = $this->di->getEventsManager();
        $eventsManager->attach('di:afterServiceResolve', function (\Phalcon\Events\Event $event, \Phalcon\Di $di, array $service): void {
            if ($service['instance'] instanceof \Phalcon\Events\EventsAwareInterface) {
                $service['instance']->setEventsManager($di->getEventsManager());
            }
            if (method_exists($service['instance'], 'afterServiceResolve')) {
                $service['instance']->afterServiceResolve();
            }
        });

        $this->di->setInternalEventsManager($eventsManager);
    }
}
