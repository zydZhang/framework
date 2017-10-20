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

use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Application as MvcApplication;

/**
 * Class Application.
 *
 * @author hehui<hehui@eelly.net>
 */
class Application extends MvcApplication
{
    public function __construct()
    {
        $this->useImplicitView(false);
    }

    /**
     * Is implicit view.
     *
     * @return bool
     */
    public function isImplicitView()
    {
        return $this->_implicitView;
    }

    /**
     * Handles a MVC request.
     */
    public function handle(string $uri = null)
    {
        if (APP['env'] == 'swoole') {
            return $this->handleSwoole($uri);
        } else {
            return parent::handle($uri);
        }
    }

    /**
     * Handles a swoole request.
     */
    private function handleSwoole(string $uri = null)
    {
        $di = $this->getDI();
        /* @var \Phalcon\Mvc\Router $router */
        $router = $di->getShared('router');
        /*
         * Handle the URI pattern (if any)
         */
        $router->handle($uri);
        /*
         * Check whether use implicit views or not
         */
        if (true === $this->isImplicitView()) {
            /* @var \Phalcon\Mvc\View $view */
            $view = $di->get('view');
            $view->start();
        }
        /* @var \Phalcon\Mvc\Dispatcher $dispatcher */
        $dispatcher = $di->getShared('dispatcher');
        $dispatcher->setModuleName($router->getModuleName());
        $dispatcher->setNamespaceName($router->getNamespaceName());
        $dispatcher->setControllerName($router->getControllerName());
        $dispatcher->setActionName($router->getActionName());
        $dispatcher->setParams($router->getParams());
        $eventsManager = $this->eventsManager;
        if (false === $eventsManager->fire('application:beforeHandleRequest', $this, $dispatcher)) {
            return false;
        }
        $controller = $dispatcher->dispatch();
        $possibleResponse = $dispatcher->getReturnedValue();
        if ('boolean' == gettype($possibleResponse) && false === $possibleResponse) {
            $response = $di->getShared('response');
        } else {
            if ('string' == gettype($possibleResponse)) {
                $response = $di->getShared('response');
                $response->setContent($possibleResponse);
            } else {
                $returnedResponse = (('object' == gettype($possibleResponse)) && ($possibleResponse instanceof ResponseInterface));

                $eventsManager->fire('application:afterHandleRequest', $this, $controller);
                $view->render(
                    $dispatcher->getControllerName(),
                    $dispatcher->getActionName(),
                    $dispatcher->getParams()
                );
                $response = $di->getShared('response');
                //$content  = $view->getContent();
                $content = ob_get_contents();
                $view->finish();
                $response->setContent($content);
            }
        }
        $eventsManager->fire('application:beforeSendResponse', $this, $response);

        return $response;
    }
}
