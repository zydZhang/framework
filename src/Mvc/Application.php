<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
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
