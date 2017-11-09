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

namespace Eelly\Console\Command;

use Eelly\Di\InjectionAwareInterface;
use Eelly\Di\Traits\InjectableTrait;
use InvalidArgumentException;
use Phalcon\Events\EventsAwareInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 队列消费者.
 *
 * 该命令支持3个参数，示例如下：
 *
 * ```
 * // 异步任务的消费，默认路由消费
 * bin/console queue-consumer logger
 *
 * // 对指定路由key进行消费
 * bin/console queue-consumer logger routing_key_name
 *
 * // 对指定路由key和队列进行消费
 * bin/console queue-consumer logger routing_key_name queue_name
 * ```
 *
 * @author hehui<hehui@eelly.net>
 */
class QueueConsumerCommand extends SymfonyCommand implements InjectionAwareInterface, EventsAwareInterface
{
    use InjectableTrait;

    private const WORKER_NUM = 5;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var \Eelly\Queue\Adapter\Consumer
     */
    private $consumer;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('queue-consumer')
            ->setDescription('Queue consumer')
            ->setHelp('队列消费者');

        $this->addArgument('exchange', InputArgument::REQUIRED, '交换机名，系统设计为你的模块名，例如: logger');
        $this->addArgument('routingKey', InputArgument::OPTIONAL, '路由key', 'default_routing_key');
        $this->addArgument('queue', InputArgument::OPTIONAL, '队列名', 'default_queue');
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        // define('AMQP_DEBUG', true);
        $this->input = $input;
        $this->output = $output;
        $this->consumer = $this->createConsumer($this->input);
        while (true) {
            try {
                $this->consumer->consume(100);
            } catch (
                \PhpAmqpLib\Exception\AMQPRuntimeException |
                \PhpAmqpLib\Exception\AMQPProtocolException |
                \PhpAmqpLib\Exception\AMQPTimeoutException $e
            ) {
                $pid = getmypid();
                $connection = $this->consumer->getConnection();
                if ($connection->isConnected()) {
                    try {
                        $connection->close();
                    } catch (\PhpAmqpLib\Exception\AMQPRuntimeException $e) {
                    }
                }
                $output->writeln(sprintf('%s %d -1 "%s"', formatTime(), $pid, $e->getMessage()));
                sleep(5);
                $this->consumer = $this->createConsumer($this->input);
            }
        }
    }

    /**
     * @param array $msg
     */
    private function consumerCallback(array $msg): void
    {
        $object = $this->di->getShared($msg['class']);
        $pid = getmypid();
        $this->output->writeln(sprintf('%s %d %d "%s::%s()" start', formatTime(), $pid, $this->consumer->consumed, $msg['class'], $msg['method']));
        $start = microtime(true);
        $return = call_user_func_array([$object, $msg['method']], $msg['params']);
        $usedTime = microtime(true) - $start;
        $this->output->writeln(sprintf('%s %d %d "%s::%s()" "%s" %s', formatTime(), $pid, $this->consumer->consumed, $msg['class'], $msg['method'], json_encode($return), $usedTime));
    }

    /**
     * @param InputInterface $input
     *
     * @return \Eelly\Queue\Adapter\Consumer
     */
    private function createConsumer(InputInterface $input)
    {
        $exchange = $input->getArgument('exchange');
        $routingKey = $input->getArgument('routingKey');
        $queue = $input->getArgument('queue');

        $moduleName = ucfirst($exchange).'\\Module';
        if (!class_exists($moduleName)) {
            throw new InvalidArgumentException('Not found exchange: '.$exchange);
        }
        /**
         * @var \Eelly\Mvc\AbstractModule
         */
        $moduleObject = $this->di->getShared($moduleName);
        /*
         * 'registerAutoloaders' and 'registerServices' are automatically called
         */
        $moduleObject->registerAutoloaders($this->di);
        $moduleObject->registerServices($this->di);
        /* @var \Eelly\Queue\Adapter\Consumer $consumer */
        $queueFactory = $this->di->get('queueFactory');
        $consumer = $queueFactory->createConsumer();
        $consumer->setExchangeOptions(['name' => $exchange, 'type' => 'topic']);
        $consumer->setRoutingKey($routingKey);
        $consumer->setQueueOptions(['name' => $exchange.'.'.$routingKey.'.'.$queue]);
        $consumer->setCallback(
            function ($msgBody): void {
                $this->consumerCallback(\GuzzleHttp\json_decode($msgBody, true));
            }
        );

        return $consumer;
    }
}
