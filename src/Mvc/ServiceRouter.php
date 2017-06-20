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

/**
 * @author hehui<hehui@eelly.net>
 */
class ServiceRouter extends Router
{
    public function afterServiceResolve(): void
    {
        $this->clear();
        $this->getEventsManager()->attach('router', $this);
    }

    public function beforeCheckRoutes(\Phalcon\Events\Event $event, Router $router): void
    {
        /**
         * @var \Eelly\Mvc\ServiceApplication $application
         */
        $application = $this->getDi()->getApplication();
        foreach ($application->getModules()as $moduleName => $value) {
            $namespace = str_replace('Module', 'Logic', $value['className']);
            $router->addPost('/'.$moduleName.'/:controller/:action', [
                'namespace' => $namespace,
                'module' => $moduleName,
                'controller' => 1,
                'action' => 2,
            ])->setName($moduleName);
        }
    }

    public function afterCheckRoutes(\Phalcon\Events\Event $event, Router $router): void
    {
        /**
         * @var \Eelly\Http\ServiceRequest $request
         */
        $request = $this->getDI()->getRequest();
        $router->setParams($request->getRouteParams());
    }

    /**
     * {@inheritdoc}
     *
     * @see \Phalcon\Mvc\Router::getRewriteUri()
     */
    public function getRewriteUri()
    {
        $url = $_SERVER['REQUEST_URI'];
        $urlParts = explode('?', $url);
        if (!empty($urlParts[0])) {
            return $urlParts[0];
        }

        return '/';
    }
}
