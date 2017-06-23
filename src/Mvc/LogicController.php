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

namespace Eelly\Mvc;

use Phalcon\Mvc\Controller;

/**
 * @property \Phalcon\Config $config 系统配置
 * @property \Phalcon\Config $moduleConfig 模块配置
 * @property \Psr\Log\LoggerInterface $logger 日志对象
 * @property \Eelly\FastDFS\Client $fastdfs fastdfs
 * @property \Thumper\ConnectionRegistry $amqp
 * @property \Eelly\Queue\Adapter\AMQPFactory $amqpFactory amqp工厂
 *
 * @author hehui<hehui@eelly.net>
 */
class LogicController extends Controller
{
}
