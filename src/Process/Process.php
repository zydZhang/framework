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

namespace Eelly\Process;

use Eelly\Exception\InvalidArgumentException;
use Swoole\Process as SwooleProcess;
use function GuzzleHttp\json_decode;

class Process extends SwooleProcess
{
    /**
     * 创建消息队列.
     *
     * @return bool
     */
    public function createQueue(): bool
    {
        return $this->useQueue();
    }

    /**
     * 重建消息队列.
     *
     * @return bool
     */
    public function reCreateQueue(): bool
    {
        $this->useQueue();
        $this->freeQueue();

        return $this->useQueue();
    }

    /**
     * 发送消息.
     *
     * @param $from
     * @param $to
     * @param $msg
     *
     * @return bool
     */
    public function send($from, $to, $msg): bool
    {
        $message = json_encode(
            [
                'from' => $from,
                'to'   => $to,
                'time' => time(),
                'msg'  => $msg,
            ]
        );

        return $this->push($message);
    }

    /**
     * 接收消息.
     *
     * @param $from
     *
     * @return array
     */
    public function receive($from, $to): array
    {
        $rawMessage = $this->pop();
        if (false === $rawMessage) {
            throw new InvalidArgumentException('Queue pop data failue');
        } else {
            $message = json_decode($rawMessage, true);
        }
        // 抢到自己发送的数据
        if ($message['from'] == $from) {
            $this->push($rawMessage);
            sleep(1);

            return $this->receive($from, $to);
        } else {
            return $message;
        }
    }
}
