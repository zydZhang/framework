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

namespace Eelly\Session;

use Phalcon\Session\Factory as SessionFactory;
use Phalcon\Session\AdapterInterface;

class Factory extends SessionFactory
{
    /**
     * @param \Phalcon\Config|array config
     */
    public static function load($config): AdapterInterface
    {
        return self::loadClass("Eelly\\Session\\Adapter", $config);
    }
}
