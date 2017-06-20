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

use Predis\Client as PredisClient;

class PredisResource extends PredisClient
{
    /**
     * @param string $key
     *
     * @return number
     */
    public function delete($key)
    {
        return $this->del($key);
    }

    /**
     * @param string $key
     * @param int    $lifetime
     *
     * @return number
     */
    public function settimeout($key, $lifetime)
    {
        return $this->expire($key, $lifetime);
    }
}
