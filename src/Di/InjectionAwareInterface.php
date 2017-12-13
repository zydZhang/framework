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

namespace Eelly\Di;

use Phalcon\Di\InjectionAwareInterface as DiInjectionAwareInterface;

/**
 * @property \Phalcon\Cache\Backend $cache
 * @property \Phalcon\Mvc\Application $application
 * @property \Phalcon\Config $config 系统配置
 * @property \Eelly\SDK\EellyClient $eellyClient
 * @property \Eelly\Dispatcher\EventDispatcher $eventDispatcher
 * @property \Phalcon\Loader $loader
 * @property \Psr\Log\LoggerInterface $logger 日志对象
 * @property \Phalcon\Config $moduleConfig 模块配置
 * @property \Eelly\Queue\Adapter\AMQPFactory|\Eelly\Queue\QueueFactoryInterface $queueFactory
 * @property \Eelly\Network\HttpServer|\Eelly\Network\TcpServer $server
 *
 * @author hehui<hehui@eelly.net>
 */
interface InjectionAwareInterface extends DiInjectionAwareInterface
{
}
