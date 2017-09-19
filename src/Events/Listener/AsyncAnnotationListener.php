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

namespace Eelly\Events\Listener;

use Eelly\SDK\EellyClient;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;

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
                'traceId' => EellyClient::$traceId,
                'time'    => microtime(true),
            ];
            $producer = $this->queueFactory->createProducer();
            $producer->setExchangeOptions(['name' => '_PHEX_'.$dispatcher->getModuleName(), 'type' => 'topic']);
            $routingKey = $annotation->getNamedParameter('route') ?? $dispatcher->getModuleName();
            $producer->publish(json_encode($msgBody), $routingKey);
            if ($event->isCancelable()) {
                $event->stop();
            }
            $dispatcher->setReturnedValue(true);

            return false;
        }
    }
}
