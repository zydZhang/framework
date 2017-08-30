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

use Eelly\Di\Injectable;
use Phalcon\Mvc\ControllerInterface;

/**
 * @author hehui<hehui@eelly.net>
 */
abstract class Controller extends Injectable implements ControllerInterface
{
    final public function __construct()
    {
        $this->eventsManager->fire("controller:init", $this);
        if (method_exists($this, 'onConstruct')) {
            $this->onConstruct();
        }
    }
}
