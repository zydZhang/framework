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

namespace Eelly\Mvc;

use Eelly\SDK\User\Api\User;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

/**
 * Class WebController.
 *
 * @property \Eelly\SDK\EellyClient $eellyClient
 */
class WebController extends Controller
{
    /**
     * @var \Eelly\DTO\UserDTO
     */
    protected $user;

    public function onConstruct(): void
    {
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
            $this->view->user = $this->user = $user->getInfo();
        }
    }
}
