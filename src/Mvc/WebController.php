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

/**
 * Class WebController.
 *
 * @property \Phalcon\Session\Adapter $session
 */
class WebController extends Controller
{
    public function onConstruct(): void
    {
        // add cache service
        $this->di->setShared('cache', function () {
            $config = $this->getConfig()->cache->toArray();
            $frontend = $this->get($config['frontend'], [$config['options'][$config['frontend']]]);

            return $this->get($config['backend'], [$frontend, $config['options'][$config['backend']]]);
        });
    }

    /**
     * display other template.
     *
     * @param string $controller template name
     * @param string $action     action name
     */
    public function sendTemplateRender($controller, $action): void
    {
        $this->view->render(
            $controller,
            $action
        );
        $this->response->setContent($this->view->getContent());
    }
}
