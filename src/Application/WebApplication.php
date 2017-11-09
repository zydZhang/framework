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
use Eelly\Di\WebDi;
use Eelly\Mvc\Application;
use Eelly\SDK\EellyClient;
use Phalcon\Config;

/**
 * Class WebApplication.
 *
 * @property \Eelly\Mvc\Application $application
 */
class WebApplication extends Injectable
{
    public function __construct(array $config)
    {
        $di = new WebDi();
        $di->setShared('config', new Config($config));
        $this->setDI($di);
    }

    public function initialize()
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
        $this->initAutoload()
            ->initEventsManager()
            ->registerServices();

        return $this;
    }

    /**
     * @param string $uri
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function handle($uri = null)
    {
        $this->application->useImplicitView(true);
        $response = $this->application->handle($uri);

        return $response;
    }

    public function run(): void
    {
        $this->initialize()->handle()->send();
    }

    /**
     * @return self
     */
    private function initEventsManager()
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

        return $this;
    }

    /**
     * @return self
     */
    private function initAutoload()
    {
        $this->loader->registerNamespaces([
            ApplicationConst::$appName => 'src',
        ])->register();

        return $this;
    }

    /**
     * @return self
     */
    private function registerServices()
    {
        $this->getDI()->setShared('router', $this->config->routes);
        // eelly client service
        $this->getDI()->setShared('eellyClient', function () {
            $options = $this->getConfig()->oauth2Client->eelly->toArray();
            if (ApplicationConst::ENV_PRODUCTION === ApplicationConst::$env) {
                $eellyClient = EellyClient::init($options['options']);
            } else {
                $collaborators = [
                    'httpClient' => new \GuzzleHttp\Client(['verify' => false]),
                ];
                $eellyClient = EellyClient::init($options['options'], $collaborators, $options['providerUri']);
            }
            if ($this->has('cache')) {
                $eellyClient->getProvider()->setAccessTokenCache($this->getCache());
            }

            return $eellyClient;
        });

        return $this;
    }
}
