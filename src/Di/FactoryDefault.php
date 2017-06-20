<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */

namespace Eelly\Di;

use Phalcon\Di\Service;

class FactoryDefault extends \Phalcon\Di\FactoryDefault
{
    public function __construct()
    {
        parent::__construct();

        $this->_services = [
            'eventsManager' => new Service('eventsManager', \Phalcon\Events\Manager::class, true),
            'loader' => new Service('loader', \Phalcon\Loader::class, true),
        ];
    }
}
