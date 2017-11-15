<?php
/**
 * Created by PhpStorm.
 * User: heui
 * Date: 2017/11/14
 * Time: 15:54
 */

namespace Eelly\Console\Command;

use Eelly\Network\TcpServer;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TcpServerCommand extends SymfonyCommand
{
    protected function configure(): void
    {
        $this->setName('tcpserver')
            ->setDescription('Tcp server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $tcpServer = new TcpServer('0.0.0.0');
        $tcpServer->start();
    }
}