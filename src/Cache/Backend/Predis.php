<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */

namespace Eelly\Cache\Backend;

use Phalcon\Cache\Backend\Redis;
use Phalcon\Cache\FrontendInterface;

/**
 * @author hehui<hehui@eelly.net>
 */
class Predis extends Redis
{
    private const FRONTEND_PREFIX = [
        \Phalcon\Cache\Frontend\Data::class => '_D_',
        \Phalcon\Cache\Frontend\Igbinary::class => '_I_',
    ];

    public function __construct(FrontendInterface $frontend, $options = null)
    {
        $this->_prefix = self::FRONTEND_PREFIX[get_class($frontend)];
        parent::__construct($frontend, $options);
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Phalcon\Cache\Backend\Redis::_connect()
     */
    public function _connect(): void
    {
        $options = [
            'parameters' => $this->_options['parameters'],
            'options' => $this->_options['options'],
        ];
        static $redisClients = [];
        $clientKey = md5(serialize($options));
        if (isset($redisClients[$clientKey])) {
            $this->_redis = $redisClients[$clientKey];
        } else {
            $this->_redis = $redisClients[$clientKey] = new PredisResource($options['parameters'], $options['options']);
        }
    }
}
