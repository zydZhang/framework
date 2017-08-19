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

namespace Eelly\Doc;

use Eelly\Di\Injectable;
use Eelly\Doc\Adapter\DocumentShowInterface;

class ApiDoc extends Injectable
{
    public function display(DocumentShowInterface $documentShow): void
    {
        $documentShow->setDI($this->getDI());
        $documentShow->display();
        die;
    }
}
