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

namespace Shadon\Events\Listener;

use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;
use PhpAmqpLib\Exception\AMQPTimeoutException;

/**
 * async annotation listener.
 *
 * @author hehui<hehui@eelly.net>
 */
class AsyncAnnotationListener extends AbstractListener
{
    /**
     * 注解名称.
     */
    private const ANNOTATIONS_NAME = 'Async';

    /**
     * @param Event      $event
     * @param Dispatcher $dispatcher
     *
     * @return bool
     */
    public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher)
    {
        if ('no-cache' == $this->request->getHeader('Cache-Control')) {
            return true;
        }
        // Parse the annotations in the method currently executed
        $annotations = $this->annotations->getMethod(
            $dispatcher->getControllerClass(),
            $dispatcher->getActiveMethod()
        );
        if ($annotations->has(self::ANNOTATIONS_NAME)) {
            $annotation = $annotations->get(self::ANNOTATIONS_NAME);
            $msgBody = [
                'class'   => $dispatcher->getControllerClass(),
                'method'  => $dispatcher->getActiveMethod(),
                'params'  => $dispatcher->getParams(),
                'time'    => microtime(true),
            ];

            try {
                /* @var \Shadon\Queue\Adapter\Producer $producer */
                $producer = $this->queueFactory->createProducer();
            } catch (AMQPTimeoutException | \ErrorException $e) {
                return true;
            }
            $producer->setExchangeOptions(['name' => $dispatcher->getModuleName(), 'type' => 'topic']);
            $routingKey = $annotation->getNamedParameter('route') ?? 'default_routing_key';
            $producer->publish(json_encode($msgBody), $routingKey);

            $connection = $producer->getConnection();
            if ($connection->isConnected()) {
                try {
                    $connection->close();
                } catch (\PhpAmqpLib\Exception\AMQPRuntimeException $e) {
                }
            }
            if ($event->isCancelable()) {
                $event->stop();
            }
            $dispatcher->setReturnedValue(true);

            return false;
        }
    }
}
