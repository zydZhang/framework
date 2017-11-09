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

namespace Eelly\Test;

use Eelly\Di\InjectionAwareInterface;
use Eelly\Di\ServiceDi;
use Phalcon\Config;
use Phalcon\Di;
use Phalcon\DiInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class UnitTestCase.
 */
class UnitTestCase extends TestCase implements InjectionAwareInterface
{
    /**
     * @var DiInterface
     */
    protected $di;

    /**
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        Di::reset();
        $config = require 'var/config/config.php';
        $di = new ServiceDi();
        $di->setShared('config', new Config($config));
        \Eelly\Application\ApplicationConst::$env = $di->getConfig()->env;
        list($moduleName) = explode('\\', static::class);
        $loader = $di->getShared('loader');
        $loader->registerNamespaces([
            $moduleName => 'src/'.$moduleName,
        ]);
        $loader->register();

        $module = $di->getShared('\\'.$moduleName.'\\Module');
        $module->registerAutoloaders($di);
        $module->registerServices($di);
        $this->di = $di;
    }

    /**
     * Sets the Dependency Injector.
     *
     * @see    Injectable::setDI
     *
     * @param DiInterface $di
     *
     * @return $this
     */
    public function setDI(DiInterface $di)
    {
        $this->di = $di;

        return $this;
    }

    /**
     * Returns the internal Dependency Injector.
     *
     * @see    Injectable::getDI
     *
     * @return DiInterface
     */
    public function getDI()
    {
        if (!$this->di instanceof DiInterface) {
            return Di::getDefault();
        }

        return $this->di;
    }
}
