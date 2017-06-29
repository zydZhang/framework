<?php
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
use Eelly\Di\Traits\MagicGetTrait;
use Phalcon\DiInterface;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author hehui<hehui@eelly.net>
 */
class Command extends SymfonyCommand implements InjectionAwareInterface, EventsAwareInterface
{
    use MagicGetTrait;

    /**
     * @var DiInterface
     */
    protected $di;

    /**
     * @var ManagerInterface
     */
    protected $eventsManager;

    /**
     * {@inheritdoc}
     *
     * @see \Phalcon\Di\InjectionAwareInterface::setDI()
     */
    public function setDI(DiInterface $di)
    {
        $this->di = $di;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Phalcon\Di\InjectionAwareInterface::getDI()
     */
    public function getDI()
    {
        return $this->di;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Phalcon\Events\EventsAwareInterface::setEventsManager()
     */
    public function setEventsManager(ManagerInterface $eventsManager)
    {
        $this->eventsManager = $eventsManager;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Phalcon\Events\EventsAwareInterface::getEventsManager()
     */
    public function getEventsManager()
    {
        return $this->eventsManager;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::initialize()
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var \Eelly\Mvc\AbstractModule $moduleObject
         */
        $moduleObject = $this->di->getShared(substr(static::class, 0, strpos(static::class, '\\', 1)).'\\Module');
        /*
         * 'registerAutoloaders' and 'registerServices' are automatically called
         */
        $moduleObject->registerAutoloaders($this->di);
        $moduleObject->registerServices($this->di);
    }
}
