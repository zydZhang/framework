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

use Eelly\Exception\ClientException;
use GuzzleHttp\Psr7\ServerRequest;
use Phalcon\Http\Request as HttpRequest;

/**
 * @author hehui<hehui@eelly.net>
 */
class ServiceRequest extends HttpRequest
{
    /**
     * @return array|mixed
     */
    public function getRouteParams(): array
    {
        if (!$this->isPost()) {
            throw new ClientException(400, 'HTTP request method only support POST', $this, $this->getDI()->getResponse());
        }
        $params = $this->getPost();
        $uploadFiles = ServerRequest::normalizeFiles($_FILES);
        $params = array_replace_recursive($params, $uploadFiles);
        $this->sortNestedArrayAssoc($params);

        return $params;
    }

    private function sortNestedArrayAssoc(&$arr): bool
    {
        if (!is_array($arr)) {
            return false;
        }
        ksort($arr);
        foreach ($arr as $key => $value) {
            $this->sortNestedArrayAssoc($arr[$key]);
        }

        return true;
    }
}
