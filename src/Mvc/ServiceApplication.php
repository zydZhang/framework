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

    /**
     * initial.
     */
    public function init()
    {
        $errorHandler = $this->_dependencyInjector->getShared(ErrorHandler::class);
        $errorHandler->register();

        $this->initEventsManager();

        $config = $this->_dependencyInjector->getConfig();
        $this->registerModules($config->modules->toArray());
    }

    /**
     * {@inheritdoc}
     *
     * @see \Phalcon\Mvc\Application::handle()
     */
    public function handle($uri = null)
    {
        try {
            $response = parent::handle($uri);
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
        $this->init();
        $this->handle()->send();
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
