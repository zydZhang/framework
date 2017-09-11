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

namespace Eelly\Di;

use Phalcon\Di\Service;

/**
 * Class WebDi.
 */
class WebDi extends FactoryDefault
{
    public function __construct()
    {
        parent::__construct();
        $this->_services['annotations'] = new Service('annotations', 'Phalcon\\Annotations\\Adapter\\Memory', true);
        $this->_services['dispatcher'] = new Service('dispatcher', 'Eelly\\Dispatcher\\WebDispatcher', true);
        $this->_services['response'] = new Service('dispatcher', 'Phalcon\\Http\\Response', true);
        $this->_services['view'] = new Service('view', 'Eelly\\Mvc\\View', true);
    }
}
