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
         * @var \Eelly\Mvc\ServiceApplication
         */
        $application = $this->getDi()->getApplication();
        foreach ($application->getModules()as $moduleName => $value) {
            $namespace = str_replace('Module', 'Logic', $value['className']);
            $router->addGet('/'.$moduleName, [
                'namespace'  => $namespace,
                'module'     => $moduleName,
            ]);
            $router->addGet('/'.$moduleName.'/:controller', [
                'namespace'  => $namespace,
                'module'     => $moduleName,
                'controller' => 1,
            ]);
            $router->add('/'.$moduleName.'/:controller/:action', [
                'namespace'  => $namespace,
                'module'     => $moduleName,
                'controller' => 1,
                'action'     => 2,
            ], ['GET', 'POST'])->setName($moduleName);
        }
    }

    public function afterCheckRoutes(\Phalcon\Events\Event $event, Router $router): void
    {
        /**
         * @var \Eelly\Http\ServiceRequest $request
         */
        $request = $this->getDI()->getShared('request');
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
