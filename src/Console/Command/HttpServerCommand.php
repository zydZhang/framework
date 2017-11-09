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
use Eelly\Http\Server as HttpServer;
use Phalcon\Events\EventsAwareInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;

class HttpServerCommand extends SymfonyCommand implements InjectionAwareInterface, EventsAwareInterface
{

    use InjectableTrait;

    protected function configure(): void
    {
        $help = <<<EOF
swoole管理命令:

    <info>start</info>
    启动 swoole 服务
    <info>stop</info>
    停止 swoole 服务
    <info>close</info>
    关闭 swoole 服务
    <info>reload</info>
    平滑重启 swoole 服务
    <info>status</info>
    swoole 服务状态
    <info>list</info>
    swoole-task所有启动实例进程列表
                   
EOF;
        $this->setName('swoole:httpserver')->setDescription('swoole Http server manage')->setHelp($help);
        $this->addArgument('module', InputArgument::REQUIRED, 'Module name.');
        $this->addOption('port', '-p', InputOption::VALUE_OPTIONAL, 'listener port', 9501);
        $this->addArgument('option', InputArgument::OPTIONAL, '请求参数option：[start,stop,close,restart,reload,status,list]', 'status');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $option = $input->getArgument('option');
        throwIf(!in_array($option, ['start', 'stop', 'close', 'reload', 'status', 'list']), \Exception::class, "option is incorrect!");
        //dd($option);
        $port = (int) $input->getOption('port');
        $module = $input->getArgument('module');
        /* @var HttpServerListener $listener */
        $listener = $this->di->getShared(HttpServerListener::class, [$input, $output]);
        switch ($option) {
            case 'start':
                $this->swooleStart($listener, $module, $port, $output);
                break;
            case 'stop':
                $this->swooleStop($module, $output);
                break;
            case 'reload':
                $this->swooleReload($module, $output);
                break;
            case 'close':
                $this->swooleClose($module, $output);
                break;
            case 'status':
                $this->swooleStatus($output);
                break;
            case 'list':
                $this->swooleList($module, $output);
                break;
        }
    }

    /**
     * 启动 swoole 服务
     * 
     * @param string $listener 
     * @param string $module 模块名字
     * @param string $port 端口
     * @param OutputInterface $output 
     * @return void
     */
    private function swooleStart($listener, $module, $port, $output): void
    {
        $env = $this->config->env;
        $options = require 'var/config/' . $env . '/' . $module . '/swoole.php';
        $httpServer = new HttpServer('0.0.0.0', $port, $listener, $options);
        $this->di->setShared('swooleServer', $httpServer);
        $httpServer->start();
        $output->writeln('swooleServer start is ok');
    }

    /**
     * 获取 pid
     * 
     * @param string $module 模块
     * @param OutputInterface $output
     * @return int
     */
    private function getSwoolePid($module, $output): int
    {
        $pid = 'var/pid/' . $module . '_master.pid';
        if (!file_exists($pid)) {
            throwIf(1, \Exception::class, $pid . ' 不存在');
        }
        $pArray = explode("\n", file_get_contents($pid));
        $pidNo = $pArray[0];
        throwIf($pidNo < 1, \Exception::class, 'pid 文件master异常');
        if (!\swoole_process::kill((int) $pidNo, 0)) {
            $output->writeln("pid :{$pid} not exist \n");
            return 0;
        }
        return (int) $pidNo;
    }

    /**
     * 直接向进程发送信号,杀死swoole进程
     * 
     * @param string $module 模块
     * @param OutputInterface $output
     * @return void
     */
    private function swooleStop($module, $output): void
    {
        $output->writeln(array(
            '<info>正在停止 swoole 服务</>'
        ));
        $pid = $this->getSwoolePid($module, $output);
        \ swoole_process::kill($pid, SIGKILL);
        $output->writeln("send server stop command at " . date("y-m-d h:i:s") . "\n");
    }

    /**
     * 向主进程/管理进程发送SIGUSR1信号，将平稳地restart所有worker进程
     * 
     * @param string $module 模块
     * @param OutputInterface $output
     * @return void
     */
    private function swooleReload($module, $output): void
    {
        $output->writeln(array(
            '<info>正在重启 swoole 服务</>'
        ));
        $pid = $this->getSwoolePid($module, $output);
        \swoole_process::kill($pid, SIGUSR1);
        $output->writeln("send server reload command at " . date("y-m-d h:i:s") . "\n");
    }

    /**
     * 向主进程/管理进程发送此信号服务器将安全终止
     * 
     * @param string $module 模块
     * @param OutputInterface $output
     * @return void
     */
    private function swooleClose($module, $output): void
    {
        $output->writeln(array(
            '<info>正在关闭 swoole 服务</>'
        ));
        $pid = $this->getSwoolePid($module, $output);
        \swoole_process::kill($pid, SIGTERM);
        $output->writeln("send server close command at " . date("y-m-d h:i:s") . "\n");
    }

    /**
     * 
     * @param OutputInterface $output
     * @return void
     */
    private function swooleStatus($output): void
    {
        $output->writeln(array(
            '<info>正在查看 swoole 服务状态</>'
        ));
        $url = 'http://127.0.0.1:9501/swoole.status';
        $client = new Client();
        $res = $client->request('POST', $url);
        $output->writeln((string) $res->getBody());
        $output->writeln("send server status command at " . date("y-m-d h:i:s") . "\n");
    }

    /**
     * 
     * @param string $module 模块
     * @param OutputInterface $output
     * @return void
     */
    private function swooleList($module, $output): void
    {
        if (!function_exists('exec')) {
            exit('exec function is disabled' . PHP_EOL);
        }
        $output->writeln(array(
            '<info>本机运行的swoole-task服务进程</>'
        ));
        $task = $module ? 'php httpserver ' . $module . ' task worker' : 'task worker';
        //ps aux|grep 'task worker' |grep -v grep|awk '{print $1, $2, $6, $8, $9, $11}
        $cmd = "ps aux|grep '" . $task . "' |grep -v grep|awk '{print $1, $2, $6, $8, $9, $11}'";
        exec($cmd, $out);
        if (empty($out)) {
            exit("没有发现正在运行的swoole-task服务" . PHP_EOL);
        }
        $output->writeln("USER PID RSS(kb) STAT START COMMAND" . PHP_EOL . "\n");
        foreach ($out as $v) {
            echo $v . PHP_EOL;
        }
        $output->writeln("send server list command at " . date("y-m-d h:i:s") . "\n");
    }

}
