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

namespace Eelly\Mvc;

use Eelly\SDK\User\Api\User;

class WebController extends Controller
{
    /**
     * @var \Eelly\DTO\UserDTO
     */
    protected $user;

    public function onConstruct(): void
    {
        $accessToken = $this->session->get('accessToken');
        if ($accessToken) {
            $this->eellyClient->setAccessToken($accessToken);
            $user = new User();
            $this->view->user = $this->user = $user->info();
        }
    }
}
