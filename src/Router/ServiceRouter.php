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

namespace Eelly\Router;

use Phalcon\Text;

/**
 * @author hehui<hehui@eelly.net>
 */
class ServiceRouter extends Router
{
    public function afterServiceResolve(): void
    {
        $this->clear();
        $this->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);
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
            $router->addPost('/'.$moduleName.'/:controller/:action', [
                'namespace'  => $namespace,
                'module'     => $moduleName,
                'controller' => 1,
                'action'     => 2,
            ])->setName($moduleName);
        }
    }

    public function afterCheckRoutes(\Phalcon\Events\Event $event, Router $router): void
    {
        /**
         * @var \Eelly\Http\ServiceRequest $request
         */
        $request = $this->getDI()->getShared('request');
        if ($request->isPost()) {
            $router->setParams($request->getRouteParams());
        }
    }

    public function getControllerName()
    {
        return Text::uncamelize(parent::getControllerName());
    }
}
