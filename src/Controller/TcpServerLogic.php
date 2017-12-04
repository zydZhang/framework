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

namespace Eelly\Controller;

use Eelly\Mvc\Controller;

/**
 * Class TcpServerLogic.
 */
class TcpServerLogic extends Controller
{

    public function register(): void
    {
        $module = $this->request->getPost('module');
        $ip = $this->request->getPost('ip', null, $this->request->getClientAddress());
        // TODO 数据校验
        $port = (int)$this->request->getPost('port');
        $this->server->registerModule($module, $ip, $port);
    }
}
