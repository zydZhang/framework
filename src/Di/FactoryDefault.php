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
