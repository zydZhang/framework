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
use Eelly\Dispatcher\ServiceDispatcher;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\ValidationData;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use Phalcon\Events\Event;
use Phalcon\Http\RequestInterface;

/**
 * Class ValidateAccessTokenListener.
 */
class ValidateAccessTokenListener extends AbstractListener
{
    /**
     * @var \League\OAuth2\Server\CryptKey
     */
    private $publicKey;

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

    public function beforeExecuteRoute(Event $event, ServiceDispatcher $dispatcher): void
    {
        $needle = $dispatcher->getControllerClass().'::'.$dispatcher->getActionName();
        if (!in_array($needle, $this->whiteNameList)) {
            $header = $this->request->getHeader('authorization');
            $this->publicKey = new CryptKey($this->moduleConfig->publicKey, null, false);
            $this->validateAuthorization($this->request);
        }
    }

    private function validateAuthorization(RequestInterface $request): void
    {
        if (empty($request->getHeader('Authorization'))) {
            throw OAuthServerException::accessDenied('Missing "Authorization" header');
        }

        $header = $request->getHeader('Authorization');
        $jwt = trim(preg_replace('/^(?:\s+)?Bearer\s/', '', $header));

        try {
            // Attempt to parse and validate the JWT
            $token = (new Parser())->parse($jwt);
            if (false === $token->verify(new Sha256(), $this->publicKey->getKeyPath())) {
                throw OAuthServerException::accessDenied('Access token could not be verified');
            }

            // Ensure access token hasn't expired
            $data = new ValidationData();
            $data->setCurrentTime(time());
            if (false === $token->validate($data)) {
                throw OAuthServerException::accessDenied('Access token is invalid');
            }

            // TODO Check if token has been revoked
            /*if ($this->accessTokenRepository->isAccessTokenRevoked($token->getClaim('jti'))) {
                throw OAuthServerException::accessDenied('Access token has been revoked');
            }*/
            ApplicationConst::$oauth = $requestAttributes = [
                'oauth_access_token_id' => $token->getClaim('jti'),
                'oauth_client_id'       => $token->getClaim('aud'),
                'oauth_user_id'         => $token->getClaim('sub'),
                'oauth_scopes'          => $token->getClaim('scopes'),
            ];
            $uidDTO = ServiceDispatcher::$uidDTO;
            if (is_object($uidDTO)) {
                $uidDTO->uid = (int) $requestAttributes['oauth_user_id'];
            }
        } catch (\InvalidArgumentException $exception) {
            // JWT couldn't be parsed so return the request as is
            throw OAuthServerException::accessDenied($exception->getMessage());
        } catch (\RuntimeException $exception) {
            //JWR couldn't be parsed so return the request as is
            throw OAuthServerException::accessDenied('Error while decoding to JSON');
        }
    }
}
