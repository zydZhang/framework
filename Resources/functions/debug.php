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

if (!function_exists('dd')) {
    /**
     * 格式化显示出变量并结束.
     *
     * @param  mixed
     */
    function dd(): void
    {
        array_map(function ($x): void {
            dump($x);
        }, func_get_args());
        die;
    }
}
