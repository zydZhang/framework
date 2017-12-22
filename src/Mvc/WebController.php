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

namespace Shadon\Mvc;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Eelly\Exception\LogicException;
use Eelly\SDK\User\Api\User;

/**
 * Class WebController.
 *
 * @property \Eelly\SDK\EellyClient $eellyClient
 * @property \Phalcon\Session\Adapter $session
 */
class WebController extends Controller
{
    /**
     * @var \Eelly\DTO\UserDTO
     */
    protected $user;

    public function onConstruct(): void
    {
        // add cache service
        $this->di->setShared('cache', function () {
            $config = $this->getConfig()->cache->toArray();
            $frontend = $this->get($config['frontend'], [$config['options'][$config['frontend']]]);

            return $this->get($config['backend'], [$frontend, $config['options'][$config['backend']]]);
        });
        /* @var \League\OAuth2\Client\Token\AccessToken $accessToken */
        $accessToken = $this->session->get('accessToken');
        if ($accessToken) {
            // token 过期
            if ($accessToken->hasExpired()) {
                try {
                    $accessToken = $this->eellyClient->getAccessToken(
                        'refresh_token',
                        ['refresh_token' => $accessToken->getRefreshToken()]
                    );
                } catch (IdentityProviderException $e) {
                    $this->session->destroy();

                    return;
                }
                $this->session->set('accessToken', $accessToken);
            }
            $this->eellyClient->setAccessToken($accessToken);
            $user = new User();

            try {
                $this->view->user = $this->user = $user->getInfo();
            } catch (LogicException $e) {
                $this->session->set('accessToken', null);
                $this->response->redirect('/user/login')->send();
            }
        }
    }

    /**
     * display other template.
     *
     * @param string $controller template name
     * @param string $action     action name
     */
    public function sendTemplateRender($controller, $action): void
    {
        $this->view->render(
            $controller,
            $action
        );
        $this->response->setContent($this->view->getContent());
    }
}
