<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
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
