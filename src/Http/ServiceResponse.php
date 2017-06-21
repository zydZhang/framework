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

use Eelly\Mvc\ServiceApplication;

/**
 * @author hehui<hehui@eelly.net>
 */
class ServiceResponse extends Response
{
    public function afterServiceResolve(): void
    {
        $this->setContentType('application/json');
        $this->setHeader('Server', ServiceApplication::VERSION);
    }
}
