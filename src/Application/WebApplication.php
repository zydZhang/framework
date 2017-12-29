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

namespace Shadon\Application;

use Composer\Autoload\ClassLoader;
use Phalcon\Config;
use Shadon\Di\WebDi;
use Shadon\Loader\Loader;
use Shadon\Mvc\Application;
use Shadon\Session\Factory;

/**
 * Class WebApplication.
 *
 * @property \Shadon\Mvc\Application $application
 */
class WebApplication
{
    /**
     * @var Application
     */
    private $application;

    private $di;

    /**
     * WebApplication constructor.
     *
     * @param ClassLoader $classLoade
     */
    public function __construct(ClassLoader $classLoader)
    {
        $this->di = new WebDi();
        $this->di->setShared('loader', $classLoader);
        if (!file_exists('.env')) {
            file_put_contents('.env', preg_replace(
                    '/^APPLICATION_KEY=/m',
                    'APPLICATION_KEY='.base64_encode(random_bytes(32)),
                    file_get_contents('.env.example'))
            );
        }
        $dotenv = new \Dotenv\Dotenv(getcwd(), '.env');
        $dotenv->load();
        $appEnv = getenv('APPLICATION_ENV');
        $appKey = getenv('APPLICATION_KEY');
        $arrayConfig = require 'var/config/config.'.$appEnv.'.php';
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $arrayConfig['requestTime'] = $_SERVER['REQUEST_TIME_FLOAT'];
        } elseif (isset($_SERVER['REQUEST_TIME'])) {
            $arrayConfig['requestTime'] = $_SERVER['REQUEST_TIME'];
        } else {
            $arrayConfig['requestTime'] = microtime(true);
        }
        define('APP', [
            'env'      => $appEnv,
            'key'      => $appKey,
            'timezone' => $arrayConfig['timezone'],
            'appname'  => $arrayConfig['appName'],
        ]);
        ApplicationConst::appendRuntimeEnv(ApplicationConst::RUNTIME_ENV_FPM);
        $this->di->setShared('config', new Config($arrayConfig));
        date_default_timezone_set(APP['timezone']);
        $this->application = $this->di->getShared(Application::class);
        $this->di->setShared('application', $this->application);
        $this->di->setShared('session', function () use ($arrayConfig) {
            $options = $arrayConfig['session'] ?? [];
            throwIf(empty($options), \RuntimeException::class, 'session config cannot be empty');

            $session = Factory::load($options);
            $session->start();

            return $session;
        });
    }

    /**
     * @param string $uri
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function handle($uri = null)
    {
        $this->initAutoload()
            ->initEventsManager()
            ->registerServices();
        $this->application->useImplicitView(true);
        $response = $this->application->handle($uri);

        return $response;
    }

    public function run(): void
    {
        $this->handle()->send();
    }

    /**
     * @return self
     */
    private function initEventsManager()
    {
        $eventsManager = $this->di->getShared('eventsManager');
        $this->application->setEventsManager($eventsManager);
        $this->di->setInternalEventsManager($eventsManager);

        return $this;
    }

    /**
     * @return self
     */
    private function initAutoload()
    {
        /* @var ClassLoader $loader */
        $loader = $this->di->getShared('loader');
        $loader->addPsr4(APP['appname'].'\\', 'src');
        $loader->register();

        return $this;
    }

    /**
     * @return self
     */
    private function registerServices()
    {
        $this->di->setShared('router', $this->di->getShared('config')->routes);

        return $this;
    }
}
