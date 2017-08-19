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

use Eelly\Di\Injectable;

/**
 * Class ModuleDocumentShow.
 */
class ModuleDocumentShow extends Injectable implements DocumentShowInterface
{
    /**
     * @var string
     */
    private $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    public function display(): void
    {
        // TODO create view
        echo 'module doc '.$this->class;
    }
}
