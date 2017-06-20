<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */

namespace Eelly\Mvc\Model\MetaData;

use Eelly\Cache\Backend\Predis as BackendRedis;
use Phalcon\Cache\Frontend\Data as FrontendData;
use Phalcon\Mvc\Model\MetaData\Redis;

/**
 * class Predis.
 *
 * ```
 * use Eelly\Mvc\Model\MetaData\Predis;
 *
 * $modelMetaData = new Predis([
 *  'parameters' => [
 *      'tcp://172.18.107.120:7000',
 *      'tcp://172.18.107.120:7001',
 *      'tcp://172.18.107.120:7002',
 *      'tcp://172.18.107.120:7003',
 *      'tcp://172.18.107.120:7004',
 *      'tcp://172.18.107.120:7005',
 *  ],
 *  'options' => ['cluster' => 'redis'],
 *  'lifetime' => 172800,
 *  'statsKey' => '_PHCR_MODEL_METADATA_STATS',
 * ]);
 * ```
 *
 * @author hehui<hehui@eelly.net>
 */
class Predis extends Redis
{
    public function __construct($options = null)
    {
        if (isset($options['lifetime'])) {
            $this->_ttl = $options['lifetime'];
        }
        $this->_redis = new BackendRedis(new FrontendData(['lifetime' => $this->_ttl]), $options);
    }
}
