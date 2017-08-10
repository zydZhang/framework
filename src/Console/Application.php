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

namespace Eelly\Console;

use Phalcon\Di\InjectionAwareInterface;
use Phalcon\DiInterface;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface;
use Symfony\Component\Console\Application as ConsoleApplication;

/**
 * @author hehui<hehui@eelly.net>
 */
class Application extends ConsoleApplication implements InjectionAwareInterface, EventsAwareInterface
{
    /**
     * @var DiInterface
     */
    protected $di;

    /**
     * @var ManagerInterface
     */
    protected $eventsManager;

    /**
     * @var array
     */
    protected $modules = [];

    public function registerModules(array $modules, bool $merge = false): self
    {
        $this->modules = $merge ? array_merge($this->modules, $modules) : $modules;
        $loader = $this->di->get('loader');
        $classes = [];
        foreach ($this->modules as $value) {
            $classes[$value['className']] = $value['path'];
        }
        $loader->registerClasses($classes);
        $loader->register();
        foreach (array_keys($loader->getClasses()) as $class) {
            $this->di->getShared($class)->registerCommands($this);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Phalcon\Di\InjectionAwareInterface::setDI()
     */
    public function setDI(DiInterface $di): void
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
    public function setEventsManager(ManagerInterface $eventsManager): void
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
}
