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

namespace Eelly\Mvc\View\Engine;

use Eelly\Mvc\View;
use Handlebars\Loader\FilesystemLoader;
use Phalcon\DiInterface;
use Phalcon\Mvc\View\Engine;
use Phalcon\Mvc\View\EngineInterface;
use Phalcon\Mvc\ViewBaseInterface;

/**
 * Phalcon\Mvc\View\Engine\Handlebars
 * Adapter to use Handlebars library as templating engine.
 */
class Handlebars extends Engine implements EngineInterface
{
    /**
     * @var \Handlebars_Engine
     */
    protected $handlebars;

    /**
     * {@inheritdoc}
     *
     * @param ViewBaseInterface $view
     * @param DiInterface       $di
     */
    public function __construct(View $view, DiInterface $di = null)
    {
        $this->handlebars = new \Handlebars\Handlebars([
            'loader'          => new FilesystemLoader('', ['extension' => 'hbs']),
            'partials_loader' => new FilesystemLoader($view->getViewsDir().'/', ['extension' => 'hbs']),
        ]);

        $this->handlebars->addHelper('startInexd', new View\Engine\Handlebars\Helper\StartInexdHelper());
        $this->handlebars->addHelper('isEven', new View\Engine\Handlebars\Helper\IsEvenHelper());

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
        $content = $this->handlebars->render($path, $params);

        if ($mustClean) {
            $this->_view->setContent($content);
        } else {
            echo $content;
        }
    }
}
