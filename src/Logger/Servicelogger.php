<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */

namespace Eelly\Logger;

use Eelly\Mvc\ServiceApplication;
use Monolog\Logger;
use Phalcon\Di\InjectionAwareInterface;

class Servicelogger extends Logger implements InjectionAwareInterface
{
    private $dependencyInjector;

    public function __construct()
    {
        parent::__construct(ServiceApplication::$env);
    }

    public function afterServiceResolve(): void
    {
        $di = $this->getDI();
        $config = $di->getConfig();
        /**
         * @var \Phalcon\Dispatcher $dispatcher
         */
        $dispatcher = $di->getDispatcher();
        $this->appendName($dispatcher->getModuleName());
        $this->pushHandler(new \Monolog\Handler\StreamHandler($config['logPath'].'/app.'.date('Ymd').'.txt'));
    }

    /**
     * append logger name.
     *
     * @param string $name
     */
    public function appendName(?string $name): void
    {
        if (!empty($name)) {
            $this->name .= '.'.$name;
        }
    }

    /**
     * Sets the dependency injector.
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public function setDI(\Phalcon\DiInterface $dependencyInjector): void
    {
        $this->dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector.
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->dependencyInjector;
    }
}
