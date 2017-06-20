<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */

namespace Eelly\Mvc;

use Eelly\Error\Handler as ErrorHandler;
use Eelly\Exception\ClientException;
use Phalcon\Di;
use Phalcon\DiInterface;

/**
 * @author hehui<hehui@eelly.net>
 */
class ServiceApplication extends Application
{
    /**
     * server name and version.
     *
     * eelly phalcon swoole server
     */
    public const VERSION = 'EPSS/1.0';

    /**
     * @var string
     */
    public static $env = self::ENV_PRODUCTION;

    /**
     * @param DiInterface $di
     */
    public function __construct(DiInterface $di)
    {
        parent::__construct($di);
        $this->useImplicitView(false);
        $di->setShared('application', $this);
        $config = $di->getConfig();
        self::$env = $config->env;
        date_default_timezone_set($config->defaultTimezone);
    }

    public function run(): void
    {
        $errorHandler = $this->_dependencyInjector->getShared(ErrorHandler::class);
        $errorHandler->register();

        $this->initEventsManager();

        $config = $this->_dependencyInjector->getConfig();
        $this->registerModules($config->modules->toArray());

        try {
            $response = $this->handle();
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }
        $response->send();
    }

    private function initEventsManager(): void
    {
        /**
         * @var \Phalcon\Events\Manager $eventsManager
         */
        $eventsManager = $this->_dependencyInjector->getEventsManager();
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
             * @var \Phalcon\Http\Response $response
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
        $this->setEventsManager($eventsManager);
        $this->_dependencyInjector->setInternalEventsManager($eventsManager);
    }
}
