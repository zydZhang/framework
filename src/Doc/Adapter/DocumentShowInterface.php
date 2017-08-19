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

namespace Eelly\Doc\Adapter;

use Phalcon\Di\InjectionAwareInterface;

/**
 * Interface DocumentShowInterface.
 */
interface DocumentShowInterface extends InjectionAwareInterface
{
    /**
     * 文档展示.
     *
     * @return mixed
     */
    public function display();
}
