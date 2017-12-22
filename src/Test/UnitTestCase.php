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

namespace Shadon\Test;

use Composer\Autoload\ClassLoader;
use Phalcon\Config;
use Phalcon\Di;
use Phalcon\DiInterface;
use PHPUnit\Framework\TestCase;
use Shadon\Application\ApplicationConst;
use Shadon\Di\InjectionAwareInterface;
use Shadon\Di\ServiceDi;

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
        $di = new ServiceDi();
        $di->setShared('loader', new ClassLoader());
        $dotenv = new \Dotenv\Dotenv(getcwd(), '.env');
        $dotenv->load();
        $appEnv = getenv('APPLICATION_ENV');
        $appKey = getenv('APPLICATION_KEY');
        /**
         * @var ClassLoader $loader
         */
        $loader = $di->getShared('loader');

        $arrayConfig = require 'var/config/config.'.$appEnv.'.php';
        define('APP', [
            'env'      => $appEnv,
            'key'      => $appKey,
            'rootPath' => $arrayConfig['rootPath'],
            'timezone' => $arrayConfig['timezone'],
        ]);
        ApplicationConst::appendRuntimeEnv(ApplicationConst::RUNTIME_ENV_CLI);
        $di->setShared('config', new Config($arrayConfig));
        list($moduleName) = explode('\\', static::class);
        $loader->addPsr4($moduleName.'\\', 'src/'.$moduleName);
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
