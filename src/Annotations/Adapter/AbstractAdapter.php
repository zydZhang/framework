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

use Phalcon\Annotations\Adapter;

/**
 * @author    hehui<hehui@eelly.net>
 */
abstract class AbstractAdapter extends Adapter
{
    /**
     * Default option for cache lifetime.
     *
     * @var array
     */
    protected static $defaultLifetime = 8600;

    /**
     * Default option for prefix.
     *
     * @var string
     */
    protected static $defaultPrefix = '_D_';

    /**
     * Backend's options.
     *
     * @var array
     */
    protected $options = null;

    /**
     * Class constructor.
     *
     * @param null|array $options
     *
     * @throws \Phalcon\Mvc\Model\Exception
     */
    public function __construct($options = null)
    {
        if (!is_array($options) || !isset($options['lifetime'])) {
            $options['lifetime'] = self::$defaultLifetime;
        }
        if (!is_array($options) || !isset($options['prefix'])) {
            $options['prefix'] = self::$defaultPrefix;
        }
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @return array
     */
    public function read($key)
    {
        return $this->getCacheBackend()->get($this->prepareKey($key), $this->options['lifetime']);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param array  $data
     */
    public function write($key, $data): void
    {
        $this->getCacheBackend()->save($this->prepareKey($key), $data, $this->options['lifetime']);
    }

    /**
     * Returns the key with a prefix or other changes.
     *
     * @param string $key
     *
     * @return string
     */
    abstract protected function prepareKey($key);

    /**
     * Returns cache backend instance.
     *
     * @return \Phalcon\Cache\BackendInterface
     */
    abstract protected function getCacheBackend();
}
