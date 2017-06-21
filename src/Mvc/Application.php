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

use Phalcon\Mvc\Application as MvcApplication;

class Application extends MvcApplication
{
    /**
     * prod 线上.
     *
     * @var string
     */
    public const ENV_PRODUCTION = 'prod';

    /**
     * local 待上线
     *
     * @var string
     */
    public const ENV_STAGING = 'local';

    /**
     * test 测试.
     *
     * @var string
     */
    public const ENV_TEST = 'test';

    /**
     * dev本地.
     *
     * @var string
     */
    public const ENV_DEVELOPMENT = 'dev';
}
