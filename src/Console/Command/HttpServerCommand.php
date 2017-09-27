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

class HttpServerCommand extends SymfonyCommand implements InjectionAwareInterface, EventsAwareInterface
{
    use InjectableTrait;

    protected function configure(): void
    {
        $this->setName('httpserver')
            ->setDescription('Http server')
            ->setHelp('Builtin http server powered by swoole.');
        $this->addArgument('module', InputArgument::REQUIRED, 'Module name.');
        $this->addOption('port', '-p', InputOption::VALUE_OPTIONAL, 'listener port', 9501);
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $port = $input->getOption('port');
        $listener = $this->di->getShared(HttpServerListener::class, [$input, $output]);
        $module = $input->getArgument('module');
        $env = $this->config->env;
        $options = require 'var/config/'.$env.'/'.$module.'/swoole.php';
        $httpServer = new HttpServer('0.0.0.0', $port, $listener, $options);
        $httpServer->start();
    }
}
