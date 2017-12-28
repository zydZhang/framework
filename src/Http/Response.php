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

namespace Shadon\Http;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Phalcon\Di\InjectionAwareInterface;
use function GuzzleHttp\Psr7\stream_for;

/**
 * @author hehui<hehui@eelly.net>
 *
 * @deprecated
 */
class Response extends Psr7Response implements InjectionAwareInterface
{
    /**
     * Dependency Injector.
     *
     * @var \Phalcon\DiInterface
     */
    protected $di;

    /**
     * Sets the dependency injector.
     *
     * @param \Phalcon\DiInterface $di
     */
    public function setDI(\Phalcon\DiInterface $di): void
    {
        $this->di = $di;
    }

    /**
     * Returns the internal dependency injector.
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->di;
    }

    public function withContent(string $content)
    {
        $body = stream_for($content);

        return $this->withBody($body);
    }

    public function withJsonContent(array $content)
    {
        $body = stream_for(json_encode($content));

        return $this->withBody($body);
    }

    /**
     * Sends HTTP headers.
     *
     * @return $this
     */
    public function sendHeaders()
    {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return $this;
        }

        // headers
        $statusCode = $this->getStatusCode();
        foreach ($this->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header($name.': '.$value, false, $statusCode);
            }
        }
        // status
        header(sprintf('HTTP/%s %s %s', $this->getProtocolVersion(), $statusCode, $this->getReasonPhrase()), true, $statusCode);
        // cookies TODO

        return $this;
    }

    /**
     * Sends cookies to the client.
     */
    public function sendCookies(): void
    {
    }

    /**
     * Sends content for the current web response.
     *
     * @return $this
     */
    public function sendContent()
    {
        $contents = (string) $this->getBody();
        echo $contents;

        return $this;
    }

    /**
     * Sends HTTP headers and content.
     *
     * @return $this
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif ('cli' !== PHP_SAPI) {
            static::closeOutputBuffers(0, true);
        }

        return $this;
    }
}
