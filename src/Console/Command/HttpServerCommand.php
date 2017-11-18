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
use Eelly\Network\HttpServer;
use Eelly\Process\Process;
use Eelly\Process\ServerHealth;
use Eelly\Process\ServerMonitor;
use Phalcon\Events\EventsAwareInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class HttpServerCommand extends SymfonyCommand implements InjectionAwareInterface, EventsAwareInterface
{
    use InjectableTrait;

    private const SIGNALS = [
        'start'    => '启动服务',
        'reload'   => '重启服务',
        'plist'    => '进程列表',
        'clist'    => '连接列表',
        'shutdown' => '关闭服务器',
        'stats'    => '服务状态',
    ];

    protected function configure(): void
    {
        $this->setName('api:httpserver')
            ->setDescription('Http server');

        $help = "\n\n系统信号选项说明\n";
        $rows = [];
        foreach (self::SIGNALS as $key => $value) {
            $rows[] = [$key, $value];
        }
        $help .= consoleTableStream(['名称', '说明'], $rows);
        $this->setHelp('Builtin http server powered by swoole.'.$help);

        $this->addArgument('module', InputArgument::REQUIRED, '模块名，如: example');
        $this->addOption('daemonize', '-d', InputOption::VALUE_NONE, '是否守护进程化');
        $this->addOption('port', '-p', InputOption::VALUE_OPTIONAL, '监听端口', 9501);
        $this->addOption('signal', '-s', InputOption::VALUE_OPTIONAL, sprintf('系统信号(%s)', implode('|', array_keys(self::SIGNALS))), 'start');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $signal = (string) $input->getOption('signal');
        if (!array_key_exists($signal, self::SIGNALS)) {
            throw new InvalidArgumentException('Signal not found');
        }
        $io = new SymfonyStyle($input, $output);
        if ('start' == $signal) {
            $module = (string) $input->getArgument('module');
            /* @var HttpServerListener $listener */
            $listener = $this->di->getShared(HttpServerListener::class, [$input, $output, $module]);
            $env = $this->config->env;
            $swooleConfig = require 'var/config/'.$env.'/'.$module.'/swoole.php';
            $options = $swooleConfig['httpserver'];
            $daemonize = $input->getOption('daemonize');
            $options['daemonize'] = $input->hasParameterOption(['--daemonize', '-d'], true);
            $options['module'] = $module;
            $httpServer = new HttpServer('0.0.0.0', (int) $input->getOption('port'), $listener, $options, $input, $output);
            $this->di->setShared('swooleServer', $httpServer);
            $httpServer->addProcess(new ServerMonitor($httpServer));
            $httpServer->addProcess(new ServerHealth($httpServer));
            $httpServer->start();
        } else {
            $process = new Process(function (): void {
            });
            $process->createQueue();
            $process->send('client', 'server', $signal);
            $message = $process->receive('client', 'server');
            if (is_array($message['msg'])) {
                $rows = [];
                foreach ($message['msg'] as $key => $value) {
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    $rows[] = [$key, $value];
                }
                $io->table(['property', 'value'], $rows);
            } elseif (is_bool($message['msg'])) {
                if ($message['msg']) {
                    $io->success(sprintf('%s %s', $signal, 'ok'));
                } else {
                    $io->warning(sprintf('%s %s', $signal, 'false'));
                }
            } else {
                $io->error('return data error');
            }
        }
    }
}
