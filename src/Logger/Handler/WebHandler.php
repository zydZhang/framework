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

namespace Shadon\Logger\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Phalcon\Di\InjectionAwareInterface;
use Shadon\Application\ApplicationConst;

/**
 * @author hehui<hehui@eelly.net>
 */
class WebHandler extends AbstractProcessingHandler implements InjectionAwareInterface
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
        $content['error'] = 'server error';
        $content['returnType'] = $record['context']['class'];
        switch (APP['env']) {
            case ApplicationConst::ENV_TEST:
            case ApplicationConst::ENV_DEVELOPMENT:
                $content['context'] = $record['context'];
                $content['error'] = $record['message'];
                break;
        }
        /* @var \Phalcon\Http\Response $response */
        $response = $this->getDI()->getResponse();
        $response = $response->setStatusCode(500, $record['level_name']);
        $response = $response->setContent($content['error']);
        // TODO view
        $response->send();
    }
}
