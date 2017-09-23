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
use Eelly\Di\ServiceDi;
use Eelly\Error\Handler as ErrorHandler;
use Eelly\Exception\LogicException;
use Eelly\Exception\RequestException;
use Eelly\Mvc\Application;
use League\OAuth2\Server\Exception\OAuthServerException;
use Phalcon\Config;
use Phalcon\Di;

/**
 * @property \Eelly\Mvc\Application $application
 *
 * @author hehui<hehui@eelly.net>
 */
class ServiceApplication extends Injectable
{
    /**
     * ServiceApplication constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $di = new ServiceDi();
        $di->setShared('config', new Config($config));
        $this->setDI($di);
    }

    /**
     * initial.
     */
    public function initialize()
    {
        $di = $this->getDI();
        $di->setShared('application', new Application($di));
        $config = $this->config;
        ApplicationConst::$env = $config->env;
        date_default_timezone_set($config->defaultTimezone);
        $errorHandler = $di->getShared(ErrorHandler::class);
        $errorHandler->register();
        $this->initEventsManager();
        foreach ($config->appBundles as $bundle) {
            $di->getShared($bundle->class, $bundle->params)->register();
        }
        $this->application->useImplicitView(false);
        $this->application->registerModules($this->config->modules->toArray());
        return $this;
    }

    /**
     * @param string $uri
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function handle($uri = null)
    {
        try {
            $response = $this->application->handle($uri);
        } catch (LogicException $e) {
            $response = $this->response;
            $response->setHeader('returnType', get_class($e));
            $content = ['error' => $e->getMessage(), 'returnType' => get_class($e)];
            $content['context'] = $e->getContext();
            $response->setJsonContent($content);
        } catch (RequestException $e) {
            $response = $e->getResponse();
        } catch (OAuthServerException $e) {
            $response = $this->response;
            $this->response->setStatusCode($e->getHttpStatusCode());
            // TODO RFC 6749, section 5.2 Add "WWW-Authenticate" header
            $this->response->setJsonContent([
                'error'   => $e->getErrorType(),
                'message' => $e->getMessage(),
                'hint'    => $e->getHint(),
            ]);
        }

        return $response;
    }

    /**
     * run.
     */
    public function run(): void
    {
        $this->initialize()->handle()->send();
    }

    private function initEventsManager()
    {
        /**
         * @var \Phalcon\Events\Manager
         */
        $eventsManager = $this->eventsManager;
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
            if (is_object($returnedValue)) {
                $this->response->setHeader('returnType', get_class($returnedValue));
                if ($returnedValue instanceof \JsonSerializable) {
                    $this->response->setJsonContent(['data' => $returnedValue, 'returnType' => get_class($returnedValue)]);
                }
            } elseif (is_array($returnedValue)) {
                $this->response->setHeader('returnType', 'array');
                $this->response->setJsonContent(['data' => $returnedValue, 'returnType' => 'array']);
            } elseif (is_scalar($returnedValue)) {
                $this->response->setHeader('returnType', gettype($returnedValue));
                $this->response->setJsonContent(
                    ['data' => $returnedValue, 'returnType' => gettype($returnedValue)]
                );
                if (is_string($returnedValue)) {
                    $dispatcher->setReturnedValue($this->response->getContent());
                }
            }
        });
        $this->application->setEventsManager($eventsManager);
        $this->di->setInternalEventsManager($eventsManager);

        return $this;
    }
}
