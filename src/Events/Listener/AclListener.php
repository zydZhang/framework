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

use Eelly\OAuth2\Client\Provider\EellyProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;

/**
 * @author hehui<hehui@eelly.net>
 */
class AclListener extends AbstractListener
{
    public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher)
    {
        $controllerName = $dispatcher->getControllerClass();
        $header = $this->request->getHeader('authorization');
        $token = trim(preg_replace('/^(?:\s+)?Bearer\s/', '', $header));
        $provider = $this->eellyClient->getProvider();
        $psr7Request = $provider->getAuthenticatedRequest(EellyProvider::METHOD_POST, $provider->getBaseAuthorizationUrl(), $token);
        try {
            $provider->getParsedResponse($psr7Request);
        } catch (IdentityProviderException $e) {
            $this->response->setStatusCode(401);
            $this->response->setJsonContent($e->getResponseBody());
            if ($event->isCancelable()) {
                $event->stop();
            }

            return false;
        }
    }
}