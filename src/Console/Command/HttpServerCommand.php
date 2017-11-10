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
use Eelly\Events\Listener\HttpServerListener;
use Eelly\Exception\InvalidArgumentException;
use Eelly\Http\Server as HttpServer;
use Eelly\Process\ServerMonitor;
use Phalcon\Events\EventsAwareInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HttpServerCommand extends SymfonyCommand implements InjectionAwareInterface, EventsAwareInterface
{
    use InjectableTrait;

    private const SIGNALS = [
        'start'  => SIGIO,
        'reload' => SIGUSR1,
        'quit'   => SIGINT,
        'stop'   => SIGKILL,
        'status' => SIGUSR2,
    ];

    protected function configure(): void
    {
        $this->setName('httpserver')
            ->setDescription('Http server')
            ->setHelp('Builtin http server powered by swoole.');
        $this->addArgument('module', InputArgument::REQUIRED, '模块名，如: example');
        $this->addOption('daemonize', '-d', InputOption::VALUE_OPTIONAL, '是否守护进程化', false);
        $this->addOption('port', '-p', InputOption::VALUE_OPTIONAL, '监听端口', 9501);
        $this->addOption('signal', '-s', InputOption::VALUE_OPTIONAL, '系统信号(start|reload|quit|stop|status)', 'start');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $signal = (string) $input->getOption('signal');
        if (!array_key_exists($signal, self::SIGNALS)) {
            throw new InvalidArgumentException('Signal not found');
        }
        if ('start' == $signal) {
            $module = (string) $input->getArgument('module');
            /* @var HttpServerListener $listener */
            $listener = $this->di->getShared(HttpServerListener::class, [$input, $output, $module]);
            $env = $this->config->env;
            $options = require 'var/config/'.$env.'/'.$module.'/swoole.php';
            $daemonize = $input->getOption('daemonize');
            $options['daemonize'] = $daemonize;
            $options['module'] = $module;
            $httpServer = new HttpServer('0.0.0.0', (int) $input->getOption('port'), $listener, $options, $input, $output);
            $this->di->setShared('swooleServer', $httpServer);

            $serverMonitor = new ServerMonitor($httpServer);
            $httpServer->addProcess($serverMonitor);
            $httpServer->start();
        }
    }
}
