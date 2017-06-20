<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */

if (!function_exists('dd')) {
    /**
     * 格式化显示出变量并结束.
     *
     * @param  mixed
     */
    function dd(): void
    {
        array_map(function ($x): void { dump($x); }, func_get_args());
        die;
    }
}
