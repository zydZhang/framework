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

namespace Eelly\Http;

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
            return [];
        }
        if (0 === strpos($this->getHeader('Content-Type'), 'application/json')) {
            $params = json_decode($this->getRawBody(), true);
        } else {
            $params = $this->getPost();
        }
        $uploadFiles = ServerRequest::normalizeFiles($_FILES);
        $params = array_replace_recursive($params, $uploadFiles);
        $this->sortNestedArrayAssoc($params);

        return $params;
    }

    private function sortNestedArrayAssoc($arr): bool
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
