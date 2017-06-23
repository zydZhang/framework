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

use Eelly\Queue\Adapter\AMQPFactory;
use Eelly\SDK\EellyClient;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\DiInterface as Di;
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
     * @param Di $di
     */
    public function registerAutoloaders(Di $di = null): void
    {
        /**
         * @var \Phalcon\Loader $loader
         */
        $loader = $di->getLoader();
        $loader->registerNamespaces([
            static::NAMESPACE => static::NAMESPACE_DIR,
        ]);
        $loader->register();

        $this->registerUserAutoloaders($di);
    }

    /**
     * Registers a user autoloader related to the module.
     *
     * @param Di $di
     */
    abstract public function registerUserAutoloaders(Di $di): void;

    /**
     * @param Di $di
     */
    public function registerConfig(Di $di): void
    {
        $moduleName = $this->moduleName;
        $di->setShared('moduleConfig', require 'var/config/'.ServiceApplication::$env.'/'.$moduleName.'.php');
    }

    /**
     * Registers services related to the module.
     *
     * @param Di $di
     */
    public function registerServices(Di $di): void
    {
        $this->registerConfig($di);
        // cache service
        $di->setShared('cache', function () {
            $config = $this->getModuleConfig()->cache->toArray();
            $frontend = $this->get($config['frontend'], [$config['options'][$config['frontend']]]);

            return $this->get($config['backend'], [$frontend, $config['options'][$config['backend']]]);
        });
        // annotations service
        $di->setShared('annotations', function () {
            $config = $this->getModuleConfig()->annotations->toArray();

            return $this->get($config['adapter'], [$config['options'][$config['adapter']]]);
        });
        // eelly client service
        $di->setShared('eellyClient', function () {
            $options = $this->getModuleConfig()->oauth2Client->eelly->toArray();
            $eellyClient = EellyClient::init($options);
            $eellyClient->getProvider()->setAccessTokenCache($this->getCache());

            return $eellyClient;
        });
        // register user services
        $this->registerUserServices($di);
        // attach events
        $this->attachUserEvents($di);
    }

    /**
     * Registers user services related to the module.
     *
     * @param Di $di
     */
    abstract public function registerUserServices(Di $di): void;

    /**
     * @param Di $di
     */
    abstract public function attachUserEvents(Di $di): void;

    /**
     * Register db service.
     *
     * @param Di $di
     */
    protected function registerDbService(Di $di)
    {
        // mysql master connection service
        $di->setShared('dbMaster', function () {
            $config = $this->getModuleConfig()->mysql->master;

            return new Mysql($config->toArray());
        });

        // mysql slave connection service
        $di->setShared('dbSlave', function () {
            $config = $this->getModuleConfig()->mysql->slave->toArray();
            shuffle($config);

            return new Mysql(current($config));
        });

        // register modelsMetadata service
        $di->setShared('modelsMetadata', function () {
            $config = $this->getModuleConfig()->mysql->metaData->toArray();

            return $this->get($config['adapter'], [$config['options'][$config['adapter']]]);
        });
        /**
         * @var \Phalcon\Events\Manager $eventsManager
         */
        $eventsManager = $di->getEventsManager();

        $eventsManager->attach('db:afterConnect', function (Pdo $connection): void {
            $connection->execute('SELECT trace_?', [EellyClient::$traceId]);
        });
    }

    /**
     * Register amqp service.
     *
     * @param Di $di
     */
    protected function registerAMQPService(Di $di)
    {
        $di->setShared('amqpFactory', function () {
            $connectionOptions = $this->getModuleConfig()->amqp->toArray();

            return new AMQPFactory($connectionOptions, 'default', 'default');
        });
    }
}
