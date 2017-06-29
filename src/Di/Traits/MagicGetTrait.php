<?php
/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eelly\Di\Traits;

trait MagicGetTrait
{
    /**
     * Magic method __get.
     */
    public function __get(string $propertyName)
    {
        if ($this->di->has($propertyName)) {
            return $this->$propertyName = $this->di->getShared($propertyName);
        }

        if ('di' == $propertyName) {
            return $this->di;
        }
        trigger_error('Access to undefined property '.$propertyName);
    }
}
