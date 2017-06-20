<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */

namespace Eelly\Mvc\Collection;

/**
 * @author    hehui<hehui@eelly.net>
 */
class Manager extends \Phalcon\Mvc\Collection\Manager
{
    public function afterServiceResolve(): void
    {
        $di = $this->getDI();
        $mongodbConfig = $di->getModuleConfig()['mongodb'];
        foreach ($mongodbConfig as $key => $value) {
            $di->setShared('mongo_'.$key, [
                'className' => \MongoDB\Client::class,
                'arguments' => [
                    [
                        'type' => 'parameter',
                        'value' => $value['uri'],
                    ],
                    [
                        'type' => 'parameter',
                        'value' => $value['uriOptions']->toArray(),
                    ],
                    [
                        'type' => 'parameter',
                        'value' => $value['driverOptions']->toArray(),
                    ],
                ],
            ]);
        }
    }
}
