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

namespace Eelly\Dispatcher;

use InvalidArgumentException;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Dispatcher\Exception as DispatchException;

/**
 * @author hehui<hehui@eelly.net>
 */
class ServiceDispatcher extends Dispatcher
{
    public function afterServiceResolve(): void
    {
        $this->setControllerSuffix('Logic');
        $this->setActionSuffix('');
        $this->getEventsManager()->attach('dispatch', $this);
    }

    /**
     * @param Event      $event
     * @param Dispatcher $dispatcher
     * @param Exception  $exception
     *
     * @return bool
     */
    public function beforeException(Event $event, Dispatcher $dispatcher, \Exception $exception)
    {
        $notFoundFuntion = function (): void {
            $response = $this->getDI()->getShared('response');
            $response->setJsonContent([
                'error' => 'Not found',
            ]);
        };
        if ($exception instanceof DispatchException) {
            switch ($exception->getCode()) {
                case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                    $notFoundFuntion();

                    return false;
            }

            return false;
        }
    }

    /**
     *
     * {@inheritDoc}
     * @see \Phalcon\Dispatcher::setParams()
     */
    public function setParams($routerParams)
    {
        $class = $this->getControllerClass();
        $method = $this->getActionName();
        $classMethod = new \ReflectionMethod($class, $method);
        $parameters = $classMethod->getParameters();
        $parametersNumber = $classMethod->getNumberOfParameters();
        if (0 != $parametersNumber) {
            foreach (range(0, $parametersNumber - 1) as $i) {
                if (! isset($routerParams[$i]) && $parameters[$i]->isDefaultValueAvailable()) {
                    $routerParams[$i] = $parameters[$i]->getDefaultValue();
                }
            }
        }
        ksort($routerParams);
        $requiredParametersNumber = $classMethod->getNumberOfRequiredParameters();
        $actualParametersNumber = count($routerParams);
        if ($actualParametersNumber < $requiredParametersNumber) {
            $this->response->setStatusCode(400);
            throw new InvalidArgumentException(
                sprintf('Too few arguments, %d passed and at least %d expected', $actualParametersNumber, $requiredParametersNumber)
            );
        }
        parent::setParams($routerParams);
    }
}
