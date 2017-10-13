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

use Phalcon\Mvc\View as MvcView;

/**
 * Class View.
 */
class View extends MvcView
{
    public function afterServiceResolve(): void
    {
        $this->setViewsDir('resource/views');
        $this->registerEngines(
            [
                //'.phtml'   => 'Phalcon\Mvc\View\Engine\Php',
                '.mustache' => 'Eelly\Mvc\View\Engine\Mustache',
                '.hbs'      => 'Eelly\Mvc\View\Engine\Handlebars',
            ]
        );
    }
}
