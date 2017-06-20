<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */

namespace Eelly\Error\Handler;

use Eelly\Mvc\Application;
use Eelly\Mvc\ServiceApplication;
use Monolog\Handler\AbstractProcessingHandler;
use Phalcon\Di\InjectionAwareInterface;

/**
 * @author hehui<hehui@eelly.net>
 */
class ServiceHandler extends AbstractProcessingHandler implements InjectionAwareInterface
{
    /**
     * Dependency Injector.
     *
     * @var \Phalcon\DiInterface
     */
    protected $dependencyInjector;

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

    protected function write(array $record): void
    {
        $content['error'] = $record['message'];
        switch (ServiceApplication::$env) {
            case Application::ENV_TEST:
            case Application::ENV_DEVELOPMENT:
                $content['context'] = $record['context'];
                break;
        }
        /**
         * @var \Phalcon\Http\Response $response
         */
        $response = $this->getDI()->getResponse();
        $response->setStatusCode($record['level'], $record['level_name']);
        $response->setJsonContent($content);
        $response->send();
    }
}
