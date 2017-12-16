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

namespace Eelly\Application;

/**
 * Application const variable and static variable.
 *
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
     *
     * @var string
     */
    public const APP_NAME = 'EELLY';

    /**
     * app version.
     *
     * @var string
     */
    public const APP_VERSION = '1.0';

    /**
     * fpm runtime environment.
     *
     * @var int
     */
    public const RUNTIME_ENV_FPM = 1;

    /**
     * cli runtime environment.
     *
     * @var int
     */
    public const RUNTIME_ENV_CLI = 2;

    /**
     * swoole runtime environment.
     *
     * @var int
     */
    public const RUNTIME_ENV_SWOOLE = 4;

    /**
     * app runtime environment.
     *
     * @var int
     */
    public static $runtimeEnv = 0;

    /**
     * app name.
     *
     * @var string
     */
    public static $appName = 'App';

    /**
     * oauth info.
     *
     * @var array
     */
    public static $oauth;

    /**
     * Append runtime environment.
     *
     * @param int $runtimeEnv
     */
    public static function appendRuntimeEnv(int $runtimeEnv):void
    {
        ApplicationConst::$runtimeEnv |= $runtimeEnv;
    }

    /**
     * Has runtime environment.
     *
     * @param int $runtimeEnv
     * @return bool
     */
    public static function hasRuntimeEnv(int $runtimeEnv):bool
    {
        return $runtimeEnv == (ApplicationConst::$runtimeEnv & $runtimeEnv);
    }
}
