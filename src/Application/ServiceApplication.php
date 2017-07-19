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
use Eelly\Error\Handler as ErrorHandler;
use Eelly\Exception\ClientException;
use Eelly\Mvc\Application;
use Phalcon\Di;

/**
 * @property \Eelly\Mvc\Application $application
 *
 * @author hehui<hehui@eelly.net>
 */
class ServiceApplication extends Injectable
{
    /**
     * initial.
     */
    public function initial()
    {
        $di = $this->getDI();
        $di->setShared('application', new Application($di));
        $config = $this->config;
        ApplicationConst::$env = $config->env;
        date_default_timezone_set($config->defaultTimezone);

        $errorHandler = $di->getShared(ErrorHandler::class);
        $errorHandler->register();

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
        $this->application->useImplicitView(false);
        $this->application->registerModules($this->config->modules->toArray());
        try {
            $response = $this->application->handle($uri);
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    /**
     * run.
     */
    public function run()
    {
        $this->initial()->handle()->send();
    }

    private function initEventsManager(): void
    {
        /**
         * @var \Phalcon\Events\Manager
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
        $eventsManager->attach('dispatch:afterDispatchLoop', function (\Phalcon\Events\Event $event, \Phalcon\Mvc\Dispatcher $dispatcher): void {
            $returnedValue = $dispatcher->getReturnedValue();
            /**
             * @var \Phalcon\Http\Response
             */
            $response = $this->getDI()->getResponse();
            if (is_object($returnedValue)) {
                $response->setHeader('ReturnType', get_class($returnedValue));
                if ($returnedValue instanceof \JsonSerializable) {
                    $response->setJsonContent($returnedValue);
                }
            } elseif (is_array($returnedValue)) {
                $response->setHeader('ReturnType', 'array');
                $response->setJsonContent($returnedValue);
            } elseif (is_scalar($returnedValue)) {
                $response->setHeader('ReturnType', gettype($returnedValue));
                $response->setContent($returnedValue);
            }
        });
        $this->application->setEventsManager($eventsManager);
        $this->di->setInternalEventsManager($eventsManager);
    }
}
