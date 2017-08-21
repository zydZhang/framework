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

use ReflectionClass;

/**
 * Class ServiceDocumentShow.
 */
class ServiceDocumentShow extends AbstractDocumentShow implements DocumentShowInterface
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
        $reflectionClass = new ReflectionClass($this->class);
        $interfaces = $reflectionClass->getInterfaces();
        $interface = array_pop($interfaces);
        $markdown = '### 接口列表'.PHP_EOL;
        foreach ($interface->getMethods() as $method) {
            $markdown .= "- [{$method->name}]({$_SERVER['REQUEST_URI']}/{$method->name})".PHP_EOL;
        }
        $this->echoMarkdownHtml($markdown);
    }
}
