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

use Composer\Autoload\ClassLoader;
use Eelly\Di\ServiceDi;
use Eelly\Error\Handler as ErrorHandler;
use Eelly\Exception\LogicException;
use Eelly\Exception\RequestException;
use Eelly\Mvc\Application;
use ErrorException;
use League\OAuth2\Server\Exception\OAuthServerException;
use Phalcon\Config;

/**
 * @property \Eelly\Mvc\Application $application
 *
 * @author hehui<hehui@eelly.net>
 */
class ServiceApplication
{
    /**
     * @var Application
     */
    private $application;

    private $di;

    /**
     * ServiceApplication constructor.
     *
     * @param ClassLoader $classLoader
     */
    public function __construct(ClassLoader $classLoader)
    {
        $this->di = new ServiceDi();
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
            'rootPath' => $arrayConfig['rootPath'],
            'timezone' => $arrayConfig['timezone'],
        ]);
        $this->di->setShared('config', new Config($arrayConfig));
        date_default_timezone_set(APP['timezone']);
        $this->application = $this->di->getShared(Application::class);
        $this->di->setShared('application', $this->application);
    }

    /**
     * @param string $uri
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function handle($uri = null)
    {
        $errorHandler = $this->di->getShared(ErrorHandler::class);
        $errorHandler->register();
        $this->initEventsManager();
        foreach ($this->di->getShared('config')->appBundles as $bundle) {
            $this->di->getShared($bundle)->register();
        }
        $modules = [];
        foreach ($this->di->getShared('config')->moduleList as $moduleName) {
            $namespace = ucfirst($moduleName);
            $this->di->getShared('router')->addPost('/'.$moduleName.'/:controller/:action', [
                'namespace'  => $namespace.'\\Logic',
                'module'     => $moduleName,
                'controller' => 1,
                'action'     => 2,
            ]);
            $modules[$moduleName] = [
                'className' => $namespace.'\\Module',
                'path'      => 'src/'.$namespace.'/Module.php',
            ];
        }
        $this->application->registerModules($modules);
        $response = $this->di->getShared('response');

        try {
            $this->application->handle($uri);
        } catch (LogicException $e) {
            $response->setHeader('returnType', get_class($e));
            $content = ['error' => $e->getMessage(), 'returnType' => get_class($e)];
            $content['context'] = $e->getContext();
            $response->setJsonContent($content);
        } catch (RequestException $e) {
            $response = $e->getResponse();
        } catch (OAuthServerException $e) {
            $response->setStatusCode($e->getHttpStatusCode());
            // TODO RFC 6749, section 5.2 Add "WWW-Authenticate" header
            $response->setJsonContent([
                'error'   => $e->getErrorType(),
                'message' => $e->getMessage(),
                'hint'    => $e->getHint(),
            ]);
        } catch (ErrorException $e) {
            //...
        }

        return $response;
    }

    /**
     * run.
     */
    public function run(): void
    {
        $this->handle()->send();
    }

    private function initEventsManager()
    {
        /**
         * @var \Phalcon\Events\Manager
         */
        $eventsManager = $this->di->getShared('eventsManager');
        $eventsManager->attach('dispatch:afterDispatchLoop', function (\Phalcon\Events\Event $event, \Phalcon\Mvc\Dispatcher $dispatcher): void {
            $returnedValue = $dispatcher->getReturnedValue();
            $response = $this->di->getShared('response');
            if (is_object($returnedValue)) {
                $response->setHeader('returnType', get_class($returnedValue));
                if ($returnedValue instanceof \JsonSerializable) {
                    $this->response->setJsonContent(['data' => $returnedValue, 'returnType' => get_class($returnedValue)]);
                }
            } elseif (is_array($returnedValue)) {
                $response->setHeader('returnType', 'array');
                $response->setJsonContent(['data' => $returnedValue, 'returnType' => 'array']);
            } elseif (is_scalar($returnedValue)) {
                $response->setHeader('returnType', gettype($returnedValue));
                $response->setJsonContent(
                    ['data' => $returnedValue, 'returnType' => gettype($returnedValue)]
                );
                if (is_string($returnedValue)) {
                    $dispatcher->setReturnedValue($response->getContent());
                }
            }
        });
        $this->application->setEventsManager($eventsManager);

        return $this;
    }
}
