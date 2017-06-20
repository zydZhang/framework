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

use Eelly\Events\Listener\AclListener;
use Eelly\Events\Listener\ApiLoggerListener;
use Eelly\Events\Listener\CacheAnnotationListener;
use Eelly\SDK\EellyClient;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Mvc\ModuleDefinitionInterface;

/**
 * @author hehui<hehui@eelly.net>
 */
abstract class AbstractModule implements ModuleDefinitionInterface
{
    private $moduleName;

    public function __construct()
    {
        $this->moduleName = lcfirst(static::NAMESPACE);
    }

    /**
     * Registers an autoloader related to the module.
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public function registerAutoloaders(\Phalcon\DiInterface $dependencyInjector = null): void
    {
        /**
         * @var \Phalcon\Loader $loader
         */
        $loader = $dependencyInjector->getLoader();
        $loader->registerNamespaces([
            static::NAMESPACE => static::NAMESPACE_DIR,
        ]);
        $loader->register();

        $this->registerUserAutoloaders($dependencyInjector);
    }

    /**
     * Registers a user autoloader related to the module.
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    abstract public function registerUserAutoloaders(\Phalcon\DiInterface $dependencyInjector = null): void;

    /**
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public function registerConfig(\Phalcon\DiInterface $dependencyInjector = null): void
    {
        $moduleName = $this->moduleName;
        $dependencyInjector->setShared('moduleConfig', require 'var/config/'.ServiceApplication::$env.'/'.$moduleName.'.php');
    }

    /**
     * Registers services related to the module.
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public function registerServices(\Phalcon\DiInterface $dependencyInjector): void
    {
        $this->registerConfig($dependencyInjector);
        // cache service
        $dependencyInjector->setShared('cache', function () {
            $config = $this->getModuleConfig()->cache->toArray();
            $frontend = $this->get($config['frontend'], [$config['options'][$config['frontend']]]);

            return $this->get($config['backend'], [$frontend, $config['options'][$config['backend']]]);
        });
        // annotations service
        $dependencyInjector->setShared('annotations', function () {
            $config = $this->getModuleConfig()->annotations->toArray();

            return $this->get($config['adapter'], [$config['options'][$config['adapter']]]);
        });
        // eelly client service
        $dependencyInjector->setShared('eellyClient', function () {
            $options = $this->getModuleConfig()->oauth2Client->eelly->toArray();

            return EellyClient::init($options);
        });
        // register user services
        $this->registerUserServices($dependencyInjector);
        // attach events
        /**
         * @var \Phalcon\Events\Manager $eventsManager
         */
        $eventsManager = $dependencyInjector->getEventsManager();
        $eventsManager->enablePriorities(true);
        $eventsManager->attach('application', $dependencyInjector->get(ApiLoggerListener::class), 100);
        $eventsManager->attach('db:afterConnect', function (Pdo $connection): void {
            $connection->execute('SELECT trace_?', [EellyClient::$traceId]);
        });
        $eventsManager->attach('dispatch', $dependencyInjector->get(AclListener::class), 100);
        $eventsManager->attach('dispatch', $dependencyInjector->get(CacheAnnotationListener::class), 50);
    }

    /**
     * Registers user services related to the module.
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    abstract public function registerUserServices(\Phalcon\DiInterface $dependencyInjector): void;

    /**
     * Register db service.
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    protected function registerDbService(\Phalcon\DiInterface $dependencyInjector)
    {
        // mysql master connection service
        $dependencyInjector->setShared('dbMaster', function () {
            $config = $this->getModuleConfig()->mysql->master;

            return new Mysql($config->toArray());
        });

        // mysql slave connection service
        $dependencyInjector->setShared('dbSlave', function () {
            $config = $this->getModuleConfig()->mysql->slave->toArray();
            shuffle($config);

            return new Mysql(current($config));
        });

        // register modelsMetadata service
        $dependencyInjector->setShared('modelsMetadata', function () {
            $config = $this->getModuleConfig()->mysql->metaData->toArray();

            return $this->get($config['adapter'], [$config['options'][$config['adapter']]]);
        });
    }
}
