<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */

namespace Eelly\Annotations\Adapter;

use Eelly\Cache\Backend\Predis as BackendRedis;
use Phalcon\Cache\Frontend\Data as FrontendData;

/**
 * Class Predis.
 *
 *
 * ```
 * use Eelly\Annotations\Adapter\Predis;
 *
 * $annotations = new Predis([
 *  'parameters' => [
 *      'tcp://172.18.107.120:7000',
 *      'tcp://172.18.107.120:7001',
 *      'tcp://172.18.107.120:7002',
 *      'tcp://172.18.107.120:7003',
 *      'tcp://172.18.107.120:7004',
 *      'tcp://172.18.107.120:7005',
 *  ],
 *  'options' => ['cluster' => 'redis'],
 *  'statsKey' => '_PHCR_ANNOTATIONS_STATS',
 * ]);
 * ```
 *
 * @author    hehui<hehui@eelly.net>
 *
 * @since     2017年5月18日
 *
 * @version   1.0
 */
class Predis extends AbstractAdapter
{
    /**
     * @var BackendRedis
     */
    protected $redis;

    /**
     * {@inheritdoc}
     *
     * @param array $options Options array
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->redis = new BackendRedis(new FrontendData([
            'lifetime' => $this->options['lifetime'],
        ]), $this->options);
    }

    /**
     * {@inheritdoc}
     *
     * @return BackendRedis
     */
    protected function getCacheBackend()
    {
        return $this->redis;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @return string
     */
    protected function prepareKey($key)
    {
        return (string) $key;
    }
}
