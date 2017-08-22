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
 * Class HomeDocumentShow.
 */
class HomeDocumentShow extends AbstractDocumentShow implements DocumentShowInterface
{
    public function display(): void
    {
        $moduleList = '';
        foreach ($this->config->modules as $module => $value) {
            require $value->path;
            $reflectionClass = new ReflectionClass($value->className);
            $docComment = $reflectionClass->getDocComment();
            $factory = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
            $docblock = $factory->create($docComment);
            $summary = $docblock->getSummary();
            $moduleList .= '- ['.$value->className.'](/'.$module.')  '.$summary.PHP_EOL;
        }
        $markdown = <<<EOF
## 衣联网api开放文档

### 帮助文档
[sdk-php-wiki](https://github.com/EellyDev/eelly-sdk-php/wiki)

### 模块列表
$moduleList

EOF;
        $this->echoMarkdownHtml($markdown);
    }
}
