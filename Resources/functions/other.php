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

if (!function_exists('getallheaders')) {
    /**
     * Get all headers for nginx.
     *
     * @return unknown[]
     */
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }
}

if (!function_exists('isLocalIpAddress')) {
    /**
     * 是否局域网ip.
     *
     *
     * @param string $ipAddress
     *
     * @return bool
     */
    function isLocalIpAddress($ipAddress)
    {
        return !filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}

if (!function_exists('isValidObjectId')) {
    /**
     * Check if a value is a valid ObjectId.
     *
     *
     * @param mixed $value
     *
     * @return bool
     *
     * @author hehui<hehui@eelly.net>
     */
    function isValidObjectId($value)
    {
        if ($value instanceof \MongoDB\BSON\ObjectID
            || preg_match('/^[a-f\d]{24}$/i', $value)) {
            $isValid = true;
        } else {
            $isValid = false;
        }

        return $isValid;
    }
}
