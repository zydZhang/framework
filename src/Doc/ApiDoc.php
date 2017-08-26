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

namespace Eelly\Doc;

use Eelly\Doc\Adapter\ApiDocumentShow;
use Eelly\Doc\Adapter\HomeDocumentShow;
use Eelly\Doc\Adapter\ModuleDocumentShow;
use Eelly\Doc\Adapter\ServiceDocumentShow;
use Eelly\Exception\RequestException;
use Eelly\Mvc\Controller;
use Phalcon\Mvc\View;

/**
 * Class ApiDoc.
 */
class ApiDoc extends Controller
{
    public function onConstruct(): void
    {
        $this->application->useImplicitView(true);
        $this->getDI()->setShared('view', function () {
            $view = new View();
            $view->setViewsDir(__DIR__.'/Resources/views/');
            $view->registerEngines([
                '.phtml'  => View\Engine\Php::class,
            ]);

            return $view;
        });
    }

    /**
     * @param string $module
     * @param string $class
     * @param string $method
     */
    public function display(string $module, string $class, string $method): void
    {
        $this->response->setContentType('text/html', 'utf-8');
        $this->renderBody($module, $class, $method);
        $this->view->render('apidoc', 'layout');
    }

    /**
     * @param string $module
     * @param string $class
     * @param string $method
     */
    private function renderBody(string $module, string $class, string $method): void
    {
        $request = $this->request;
        while (true) {
            if ('/' == $request->getURI()) {
                $documentShow = new HomeDocumentShow();
                break;
            }
            if (false !== strpos($class, 'Logic\\IndexLogic')) {
                $moduleClass = ucfirst($module).'\Module';
                if (class_exists($moduleClass)) {
                    $documentShow = new ModuleDocumentShow($moduleClass);
                    break;
                }
            }
            if (class_exists($class)) {
                if ('index' == $method) {
                    $documentShow = new ServiceDocumentShow($class);
                    break;
                }
                if (method_exists($class, $method)) {
                    $documentShow = new ApiDocumentShow($class, $method);
                    break;
                }
            }
            throw new RequestException(404, null, $this->request, $this->response);
        }
        $documentShow->setDI($this->getDI());
        $documentShow->renderBody();
    }
}
