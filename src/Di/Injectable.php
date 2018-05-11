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

namespace Shadon\Di;

use Phalcon\Db\Profiler;
use Phalcon\Di\Injectable as DiInjectable;
use Shadon\Db\Adapter\Pdo\Mysql as Connection;
use Shadon\Queue\Adapter\AMQPFactory;

/**
 * @author hehui<hehui@eelly.net>
 */
abstract class Injectable extends DiInjectable implements InjectionAwareInterface
{
    /**
     * Register db service.
     */
    public function registerDbService(): void
    {
        $di = $this->getDI();
        $di->setShared('dbProfiler', function () {
            return new Profiler();
        });
        // mysql master connection service
        $di->setShared('dbMaster', function () {
            $config = $this->getModuleConfig()->mysql->master;

            $connection = new Connection($config->toArray());
            $connection->setEventsManager($this->get('eventsManager'));

            return $connection;
        });

        // mysql slave connection service
        $di->setShared('dbSlave', function () {
            $config = $this->getModuleConfig()->mysql->slave->toArray();
            shuffle($config);

            $connection = new Connection(current($config));
            $eventsManager = $this->get('eventsManager');
            $connection->setEventsManager($eventsManager);

            $profiler = $this->get('dbProfiler');
            $eventsManager->attach('db', function ($event, $connection) use ($profiler): void {
                if ('beforeQuery' === $event->getType()) {
                    $profiler->startProfile(
                        $connection->getSQLStatement()
                    );
                }
                if ('afterQuery' === $event->getType()) {
                    $profiler->stopProfile();
                }
            });

            return $connection;
        });

        // register modelsMetadata service
        $di->setShared('modelsMetadata', function () {
            $config = $this->getModuleConfig()->mysql->metaData->toArray();

            return $this->get($config['adapter'], [
                $config['options'][$config['adapter']],
            ]);
        });
    }

    /**
     * Register queue service.
     */
    public function registerQueueService(): void
    {
        $this->getDI()->set('queueFactory', function () {
            $connectionOptions = $this->getModuleConfig()->amqp->toArray();

            return new AMQPFactory($connectionOptions);
        });
    }
}
