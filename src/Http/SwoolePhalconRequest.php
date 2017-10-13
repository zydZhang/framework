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

namespace Eelly\Http;

use Phalcon\Http\Request as HttpRequest;
use swoole_http_request as SwooleHttpRequest;

class SwoolePhalconRequest extends HttpRequest
{
    /**
     * @var SwooleHttpRequest
     */
    private $swooleHttpRequest;

    public function initialWithSwooleHttpRequest(SwooleHttpRequest $swooleHttpRequest): void
    {
        $this->swooleHttpRequest = $swooleHttpRequest;
        foreach ($swooleHttpRequest->server as $key => $value) {
            $_SERVER[strtoupper($key)] = $value;
        }
    }

    /**
     * @return SwooleHttpRequest
     */
    public function getSwooleHttpRequest()
    {
        return $this->swooleHttpRequest;
    }
}
