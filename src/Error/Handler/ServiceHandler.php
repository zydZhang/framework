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

namespace Eelly\Error\Handler;

use Eelly\Application\ApplicationConst;
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
        switch (ApplicationConst::$env) {
            case ApplicationConst::ENV_TEST:
            case ApplicationConst::ENV_DEVELOPMENT:
                $content['context'] = $record['context'];
                break;
        }
        /**
         * @var \Phalcon\Http\Response
         */
        $response = $this->getDI()->getResponse();
        $response->setStatusCode(500, $record['level_name']);
        $response->setJsonContent($content);
        if ('swoole' != APP['env']) {
            $response->send();
        }
    }
}
