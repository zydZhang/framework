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

use Eelly\Network\TcpServer;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TcpServerCommand extends SymfonyCommand
{
    protected function configure(): void
    {
        $this->setName('api:tcpserver')
            ->setDescription('Tcp server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $tcpServer = new TcpServer('0.0.0.0');
        $tcpServer->start();
    }
}
