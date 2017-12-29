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

namespace Shadon\Client;

use ErrorException;
use GuzzleHttp\Psr7\MultipartStream;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Shadon\OAuth2\Client\Provider\ShadonProvider;

/**
 * Class ShadonSDKClient.
 *
 * @author hehui<hehui@eelly.net>
 */
class ShadonSDKClient
{
    /**
     * service map.
     *
     * @var array
     */
    protected $serviceMap = [];

    /**
     * @var AbstractProvider
     */
    private $provider;

    /**
     * @var string
     */
    private $grant;

    /**
     * @var array
     */
    private $requestOptions;

    /**
     * @var static
     */
    private static $self;

    /**
     * @var \League\OAuth2\Client\Token\AccessToken
     */
    private $accessToken;

    /**
     * ShadonSDKClient constructor.
     *
     * @param ShadonProvider $provider
     * @param array          $serviceMap
     * @param string         $grant
     * @param array          $requestOptions
     */
    private function __construct(ShadonProvider $provider, array $serviceMap, $grant = 'client_credentials', array $requestOptions = [])
    {
        $this->provider = $provider;
        $this->serviceMap = array_replace($this->serviceMap, $serviceMap);
        $this->grant = $grant;
        $this->requestOptions = $requestOptions;
    }

    /**
     * @param ShadonProvider $provider
     * @param array          $serviceMap
     * @param string         $grant
     * @param array          $requestOptions
     *
     * @return ShadonSDKClient
     */
    public static function fromProvider(ShadonProvider $provider, array $serviceMap, $grant = 'client_credentials', array $requestOptions = []): self
    {
        if (null === self::$self) {
            self::$self = new static($provider, $serviceMap, $grant, $requestOptions);
        }

        return self::$self;
    }

    /**
     * @return ShadonProvider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param AccessToken $accessToken
     */
    public function setAccessToken(AccessToken $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return AccessToken
     */
    public function getAccessToken()
    {
        return $this->provider->getAccessToken($this->grant, $this->requestOptions);
    }

    /**
     * @param string $uri
     * @param array  ...$args
     *
     * @return mixed
     */
    public function request(string $uri, ...$args)
    {
        $promise = $this->requestAsync($uri, $args);
        $response = $promise->wait();

        return $response;
    }

    /**
     * @param string $uri
     * @param array  ...$args
     *
     * @throws ErrorException
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function requestAsync(string $uri, ...$args)
    {
        if (null === $this->accessToken || $this->accessToken->hasExpired()) {
            $this->accessToken = $this->getAccessToken();
        }
        $stream = new MultipartStream($this->paramsToMultipart($args));
        $options = [
            'body' => $stream,
        ];
        $serviceName = explode('/', $uri)[0];
        if (!array_key_exists($serviceName, $this->serviceMap)) {
            throw new ErrorException('Service not found:'.$serviceName);
        }
        $token = $this->accessToken->getToken();
        $request = $this->provider->getAuthenticatedRequest('POST', $this->serviceMap[$serviceName].'/'.$uri, $token, $options);
        $promise = $this->provider->getHttpClient()->sendAsync($request, [
            'timeout' => 5,
            //'debug' => true,
        ]);

        return $promise;
    }

    /**
     * @param $params
     * @param null $prefix
     *
     * @return array
     */
    private function paramsToMultipart($params, $prefix = null)
    {
        $multipart = [];
        foreach ($params as $key => $value) {
            $p = null === $prefix ? $key : $prefix.'['.$key.']';
            if ($value instanceof UploadedFileInterface) {
                $multipart[] = [
                    'name'     => $p,
                    'contents' => $value->getStream(),
                ];
            } elseif (is_array($value)) {
                $parentMultipart = $this->paramsToMultipart($value, $p);
                foreach ($parentMultipart as $part) {
                    $multipart[] = $part;
                }
            } elseif (null !== $value) {
                $multipart[] = [
                    'name'     => $p,
                    'contents' => $value,
                ];
            }
        }

        return $multipart;
    }
}
