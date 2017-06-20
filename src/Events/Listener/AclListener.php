<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */

namespace Eelly\Events\Listener;

use Eelly\OAuth2\Client\Provider\EellyProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;

/**
 * @author hehui<hehui@eelly.net>
 */
class AclListener extends AbstractListener
{
    public function beforeDispatch(Event $event, Dispatcher $dispatcher)
    {
        $controllerName = $dispatcher->getControllerClass();
        // 白名单
        if (in_array($controllerName, [
            'Oauth\Logic\AuthorizationserverLogic',
            'Oauth\Logic\ResourceserverLogic',
        ])) {
            return;
        }
        /**
         * @var \Phalcon\Http\Request $request
         */
        $request = $this->getDI()->getRequest();
        $header = $this->request->getHeader('authorization');
        $token = trim(preg_replace('/^(?:\s+)?Bearer\s/', '', $header));
        /**
         * @var \Eelly\OAuth2\Client\Provider\EellyProvider $provider
         */
        $provider = $this->getDI()->getEellyClient()->getProvider();
        $psr7Request = $provider->getAuthenticatedRequest(EellyProvider::METHOD_POST, $provider->getBaseAuthorizationUrl(), $token);
        try {
            $provider->getParsedResponse($psr7Request);
        } catch (IdentityProviderException $e) {
            /**
             * @var \Phalcon\Http\Response $response
             */
            $response = $this->getDI()->getResponse();
            $response->setStatusCode(401);
            $response->setJsonContent($e->getResponseBody());

            return false;
        }
    }
}
