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

use Shadon\Application\ApplicationConst;

/**
 * @author hehui<hehui@eelly.net>
 *
 * @deprecated
 */
class Psr7ServiceResponse extends Response
{
    public function __construct(
        $status = 200,
        array $headers = [],
        $body = null,
        $version = '1.1',
        $reason = null
    ) {
        if (empty($headers)) {
            $headers = [
                'Content-Type'                => 'application/json',
                'Access-Control-Allow-Origin' => '*',
                'Server'                      => ApplicationConst::APP_NAME.'/'.ApplicationConst::APP_VERSION,
            ];
        }
        parent::__construct($status, $headers, $body, $version, $reason);
    }
}
