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

namespace Eelly\Http\Traits;

use Phalcon\Http\Request as PhalconRequest;
use swoole_http_request as SwooleRequest;

/**
 * @author hehui<hehui@eelly.net>
 */
trait RequestTrait
{
    /**
     * Request convert.
     *
     * @param SwooleRequest  $swooleRequest
     * @param PhalconRequest $phalconRequest
     */
    private function convertSwooleRequestToPhalconRequest(SwooleRequest $swooleRequest, PhalconRequest $phalconRequest): void
    {
        foreach ($swooleRequest->server as $key => $value) {
            $_SERVER[strtoupper($key)] = $value;
        }
    }
}
