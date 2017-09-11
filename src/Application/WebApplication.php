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

use Eelly\Di\Injectable;
use Eelly\Mvc\Application;

/**
 * Class WebApplication.
 *
 * @property \Eelly\Mvc\Application $application
 */
class WebApplication extends Injectable
{
    public function initial()
    {
        $di = $this->getDI();
        $di->setShared('application', new Application($di));
        $config = $this->config;
        ApplicationConst::$env = $config->env;
        ApplicationConst::$appName = $config->appName;
        date_default_timezone_set($config->defaultTimezone);
        // TODO WebHandler
        //$errorHandler = $di->getShared(ErrorHandler::class);
        //$errorHandler->register();
        $this->initEventsManager();

        return $this;
    }

    /**
     * @param string $uri
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function handle($uri = null)
    {
        $this->loader->registerNamespaces([
            ApplicationConst::$appName => 'src',
        ])->register();
        $this->getDI()->set('router', function () {
            $router = require 'var/config/routes.php';

            return $router;
        });
        $response = $this->application->handle($uri);

        return $response;
    }

    public function run(): void
    {
        $this->initial()->handle()->send();
    }

    public function initEventsManager(): void
    {
        $eventsManager = $this->eventsManager;
        $eventsManager->attach('di:afterServiceResolve', function (\Phalcon\Events\Event $event, \Phalcon\Di $di, array $service): void {
            if ($service['instance'] instanceof \Phalcon\Events\EventsAwareInterface) {
                $service['instance']->setEventsManager($di->getEventsManager());
            }
            if (method_exists($service['instance'], 'afterServiceResolve')) {
                $service['instance']->afterServiceResolve();
            }
        });
        $this->application->setEventsManager($eventsManager);
        $this->di->setInternalEventsManager($eventsManager);
    }
}
