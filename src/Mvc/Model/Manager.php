<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */

namespace Eelly\Mvc\Model;

use Eelly\Mvc\Model\Query\Builder;
use Phalcon\Mvc\Model\Manager as ModelManager;

class Manager extends ModelManager
{
    /**
     * {@inheritdoc}
     *
     * @see ModelManager::createBuilder()
     */
    public function createBuilder($params = null)
    {
        return $this->getDI()->get(
            Builder::class,
            [
                $params,
                $this->getDI(),
            ]
            );
    }
}
