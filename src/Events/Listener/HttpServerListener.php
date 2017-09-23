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

use swoole_http_request as HttpRequest;
use swoole_http_response as HttpResponse;

class HttpServerListener extends AbstractListener
{
    public function onStart(): void
    {
    }

    public function onShutdown(): void
    {
    }

    public function onWorkerStart(): void
    {
    }

    public function onWorkerStop(): void
    {
    }

    public function onTimer(): void
    {
    }

    public function onRequest(HttpRequest $httpRequest, HttpResponse $httpResponse): void
    {
        $httpResponse->end('<h1>Hello Swoole. #'.random_int(1000, 9999).'</h1>');
    }

    public function onPacket(): void
    {
    }

    public function onClose(): void
    {
    }

    public function onBufferFull(): void
    {
    }

    public function onBufferEmpty(): void
    {
    }

    public function onTask(): void
    {
    }

    public function onFinish(): void
    {
    }

    public function onPipeMessage(): void
    {
    }

    public function onWorkerError(): void
    {
    }

    public function onManagerStart(): void
    {
    }

    public function onManagerStop(): void
    {
    }
}
