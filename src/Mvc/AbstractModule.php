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

namespace Shadon\Mvc;

use Phalcon\Config;
use Phalcon\DiInterface as Di;
use Phalcon\Mvc\ModuleDefinitionInterface;
use Shadon\Application\ApplicationConst;
use Shadon\Di\Injectable;
use Shadon\Events\Listener\ValidateAccessTokenListener;
use Shadon\SDK\EellyClient;

/**
 * @author hehui<hehui@eelly.net>
 */
abstract class AbstractModule extends Injectable implements ModuleDefinitionInterface
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
        /* @var \Composer\Autoload\ClassLoader $loader */
        $loader = $di->getLoader();
        $loader->addPsr4(static::NAMESPACE.'\\', static::NAMESPACE_DIR);
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
        $di->setShared('moduleConfig', require 'var/config/'.APP['env'].'/'.$moduleName.'.php');
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
            if (ApplicationConst::ENV_PRODUCTION === APP['env']) {
                $eellyClient = EellyClient::init($options['options']);
            } else {
                $collaborators = [
                    'httpClient' => new \GuzzleHttp\Client(['verify' => false]),
                ];
                $eellyClient = EellyClient::init($options['options'], $collaborators, $options['providerUri']);
            }
            $eellyClient->getProvider()->setAccessTokenCache($this->getCache());

            return $eellyClient;
        });
        // register user services
        $this->registerUserServices($di);
        $eventsManager = $this->eventsManager;
        $eventsManager->enablePriorities(true);
        // token 校验
        if (!ApplicationConst::hasRuntimeEnv(ApplicationConst::RUNTIME_ENV_SWOOLE)) {
            $eventsManager->attach('dispatch', $di->getShared(ValidateAccessTokenListener::class), 10000);
        }
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
     * 注入模块支持的命令.
     *
     * @param \Shadon\Console\Application $app
     */
    public function registerCommands(\Shadon\Console\Application $app): void
    {
        /* @var \Composer\Autoload\ClassLoader $loader */
        $loader = $this->loader;
        $loader->addPsr4(static::NAMESPACE.'\\Command\\', static::NAMESPACE_DIR.'/Command');
        $this->registerUserCommands($app);
    }

    /**
     * Registers user commands.
     *
     * @param \Shadon\Console\Application $app
     */
    abstract public function registerUserCommands(\Shadon\Console\Application $app): void;
}
