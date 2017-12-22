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

namespace Shadon\Mvc\View\Engine;

use Phalcon\DiInterface;
use Phalcon\Mvc\View\Engine;
use Phalcon\Mvc\View\EngineInterface;
use Phalcon\Mvc\ViewBaseInterface;
use Shadon\Mvc\View;

/**
 * Phalcon\Mvc\View\Engine\Mustache
 * Adapter to use Mustache library as templating engine.
 */
class Mustache extends Engine implements EngineInterface
{
    /**
     * @var \Mustache_Engine
     */
    protected $mustache;

    /**
     * {@inheritdoc}
     *
     * @param ViewBaseInterface $view
     * @param DiInterface       $di
     */
    public function __construct(View $view, DiInterface $di = null)
    {
        $this->mustache = new \Mustache_Engine([
            'partials_loader' => new \Mustache_Loader_FilesystemLoader($view->getViewsDir().'/'.$view->getControllerName()),
            'escape'          => function ($value) {
                return $value;
            },
        ]);
        parent::__construct($view, $di);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path
     * @param array  $params
     * @param bool   $mustClean
     */
    public function render($path, $params, $mustClean = false): void
    {
        if (!isset($params['content'])) {
            $params['content'] = $this->_view->getContent();
        }
        $content = $this->mustache->render(file_get_contents($path), $params);
        if ($mustClean) {
            $this->_view->setContent($content);
        } else {
            echo $content;
        }
    }
}
