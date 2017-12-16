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

namespace Eelly\Events\Listener;

use Eelly\Application\ApplicationConst;
use Eelly\SDK\Logger\Api\ApiLogger;
use MongoDB\BSON\ObjectID;
use Phalcon\Events\Event;
use Phalcon\Http\Response;
use Phalcon\Http\Response\Headers;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Dispatcher;

/**
 * api 日志.
 *
 * @author hehui<hehui@eelly.net>
 */
class ApiLoggerListener extends AbstractListener
{
    /**
     * 跟踪id.
     *
     * @var string
     */
    private $traceId;

    /**
     * 输入信息.
     *
     * @var array
     */
    private $requestData;

    /**
     * 输出信息.
     *
     * @var array
     */
    private $responseData;

    /**
     * 额外信息.
     *
     * @var array
     */
    private $extrasData;

    /**
     * 白名单.
     *
     * @var array
     */
    private $whiteNameList;

    public function __construct(array $whiteNameList = [])
    {
        $this->whiteNameList = $whiteNameList;
    }

    /**
     * 添加白名单.
     *
     * @param string $whiteName
     */
    public function pushWhiteName(string $whiteName): void
    {
        array_push($this->whiteNameList, $whiteName);
    }

    /**
     * @param Event       $event
     * @param Application $application
     * @param Dispatcher  $dispatcher
     */
    public function beforeHandleRequest(Event $event, Application $application, Dispatcher $dispatcher): void
    {
        $controllerClass = $dispatcher->getControllerClass();
        $actionName = $dispatcher->getActionName();
        $needle = $controllerClass.'::'.$actionName;
        if (in_array($needle, $this->whiteNameList)) {
            return;
        }

        $request = $this->request;
        // 添加跟踪id
        $this->traceId = $request->getHeader('traceId');
        if (empty($this->traceId)) {
            $this->traceId = (new ObjectID())->__toString();
        } else {
            try {
                new ObjectID($this->traceId);
            } catch (\MongoDB\Driver\Exception\InvalidArgumentException $e) {
                $this->traceId = (new ObjectID())->__toString();
            }
        }
        $this->eellyClient->setTraceId($this->traceId);
        $this->requestData = [];
        $this->requestData['requestTime'] = $this->config->requestTime;
        $this->requestData['clientAddress'] = $request->getClientAddress(true);
        $this->requestData['serverAddress'] = $request->getServerAddress();
        $this->requestData['headers'] = $request->getHeaders();
        $this->requestData['URI'] = $request->getURI();
        $this->requestData['method'] = $request->getMethod();
        $this->requestData['post'] = $request->getPost();
        $this->requestData['moduleName'] = $dispatcher->getModuleName();
        $this->requestData['controllerClass'] = $controllerClass;
        $this->requestData['actionName'] = $actionName;
        $this->requestData['params'] = $this->router->getParams();
        $this->requestData['appEnv'] = APP['env'];
    }

    /**
     * @param Event       $event
     * @param Application $application
     * @param Response    $response
     */
    public function beforeSendResponse(Event $event, Application $application, Response $response): void
    {
        if (null == $this->requestData) {
            return;
        }
        $this->requestData['oauth'] = ApplicationConst::$oauth;
        $this->responseData['responseTime'] = microtime(true);
        $this->responseData['statusCode'] = $response->getStatusCode();
        $this->responseData['content'] = $response->getContent();
        $this->responseData['headers'] = $response->getHeaders()->toArray();
        $this->extrasData['usedTime'] = $this->responseData['responseTime'] - $this->requestData['requestTime'];
        $this->extrasData['usedMemory'] = memory_get_peak_usage(true);
        if ('swoole' == APP['env']) {
            $server = $this->getDI()->getShared('swooleServer');
            $server->task([
                'class'  => ApiLogger::class,
                'method' => 'log',
                'params' => [$this->traceId, $this->requestData, $this->responseData, $this->extrasData],
            ]);
        } else {
            (new ApiLogger())->log($this->traceId, $this->requestData, $this->responseData, $this->extrasData);
        }
    }
}
