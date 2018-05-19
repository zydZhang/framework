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

namespace Shadon\Queue\Adapter;

class Producer extends \Thumper\Producer
{
    private const PREFIX = 'eelly_api.';

    /**
     * @param array $options
     */
    public function setExchangeOptions(array $options): void
    {
        if (isset($options['name'])) {
            $options['name'] = self::PREFIX.$options['name'];
        }
        parent::setExchangeOptions($options);
    }

    /**
     * @return \PhpAmqpLib\Connection\AbstractConnection
     */
    public function getConnection()
    {
        return $this->channel->getConnection();
    }
}
