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

use Dotenv\Dotenv;
use Eelly\Application\ApplicationConst;
use Eelly\Network\TcpServer;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TcpServerCommand extends SymfonyCommand
{
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
        $this->setName('api:tcpserver')
            ->setDescription('Tcp server');

        $help = "\n\n系统信号选项说明\n";
        $rows = [];
        foreach (self::SIGNALS as $key => $value) {
            $rows[] = [$key, $value];
        }
        $help .= consoleTableStream(['名称', '说明'], $rows);
        $this->setHelp('Builtin tcp server powered by swoole.'.$help);

        $this->addArgument('module', InputArgument::REQUIRED, '模块名，如: example');
        $this->addOption('daemonize', '-d', InputOption::VALUE_NONE, '是否守护进程化');
        $this->addOption('port', '-p', InputOption::VALUE_OPTIONAL, '监听端口', 0);
        $this->addOption('signal', '-s', InputOption::VALUE_OPTIONAL, sprintf('系统信号(%s)', implode('|', array_keys(self::SIGNALS))), 'start');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $tcpServer = new TcpServer('0.0.0.0', (int) $input->getOption('port'));
        $module = (string) $input->getArgument('module');
        $this->overloadDotEnv();
        $env = getenv('APPLICATION_ENV');
        ApplicationConst::$env = $env;
        $swooleConfig = require 'var/config/'.$env.'/'.$module.'/swoole.php';
        $options = $swooleConfig['tcpserver'];
        $tcpServer->set($options);
        $tcpServer->setModule($module);
        $tcpServer->setOutput($output);
        $tcpServer->start();
    }

    private function overloadDotEnv(): void
    {
        $dotenv = new Dotenv(getcwd(), '.env');
        $dotenv->overload();
    }
}
