<?php
/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eelly\Application;

/**
 * @author hehui<hehui@eelly.net>
 */
final class ApplicationConst
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

    /**
     * app name.
     */
    public const APP_NAME = 'EELLY';

    /**
     * app version.
     */
    public const APP_VERSION = '1.0';

    /**
     * @var string
     */
    public static $env = self::ENV_PRODUCTION;
}
