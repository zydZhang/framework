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

use Eelly\Application\ApplicationConst;

/**
 * @author hehui<hehui@eelly.net>
 */
class ServiceResponse extends Response
{
    public function afterServiceResolve(): void
    {
        $this->setHeader('Access-Control-Allow-Origin', '*');
        $this->setHeader('Server', ApplicationConst::APP_NAME.'/'.ApplicationConst::APP_VERSION);
        $this->setStatusCode(200);
    }
}
