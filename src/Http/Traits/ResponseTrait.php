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

use Phalcon\Http\Response as PhalconResponse;
use swoole_http_response as SwooleResponse;

/**
 * @author hehui<hehui@eelly.net>
 */
trait ResponseTrait
{
    /**
     * response convert.
     *
     * @param PhalconResponse    $phalconResponse
     * @param SwooleHttpResponse $swooleHttpResponse
     */
    private function convertPhalconResponseToSwooleResponse(PhalconResponse $phalconResponse, SwooleResponse $swooleResponse): void
    {
        $swooleResponse->status($phalconResponse->getStatusCode());
        foreach ($phalconResponse->getHeaders()->toArray() as $key => $value) {
            $swooleResponse->header($key, (string) $value);
        }
        // TODO cookie
    }
}
