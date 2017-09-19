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

namespace Eelly\Queue\Adapter;

class Consumer extends \Thumper\Consumer
{
    private const EXCHANGE_PREFIX = 'eelly.api.';
    private const QUEUE_PREFIX = 'Eelly\\Api\\';

    /**
     * @param array $options
     */
    public function setExchangeOptions(array $options): void
    {
        if (isset($options['name'])) {
            $options['name'] = self::EXCHANGE_PREFIX.$options['name'];
        }
        parent::setExchangeOptions($options);
    }

    /**
     * @param array $options
     */
    public function setQueueOptions(array $options): void
    {
        if (isset($options['name'])) {
            $options['name'] = self::QUEUE_PREFIX.$options['name'];
        }
        parent::setQueueOptions($options);
    }
}
