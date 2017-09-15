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
    public function renderBody(): void
    {
        $moduleList = <<<EOF
### 已交付的模块列表
模块名|模块说明
-----|-------\n
EOF;

        foreach ($this->config->modules as $module => $value) {
            require_once $value->path;
            $reflectionClass = new ReflectionClass($value->className);
            $docComment = $reflectionClass->getDocComment();
            $factory = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
            $docblock = $factory->create($docComment);
            $summary = $docblock->getSummary();
            $moduleName = substr($value->className,0, -7);
            $moduleList .= '['.$moduleName.'](/'.$module.')|'.$summary.PHP_EOL;
        }
        $markdown = <<<EOF
# 衣联网api开放文档

> 本文档由接口代码文档生成，接口文档使用[github markdown](https://guides.github.com/features/mastering-markdown/)风格

> 贡献接口的phper请完善好接口文档

> 特殊注解说明(`@requestExample`表示请求示例，`@returnExample`表示返回数据示例)

### 接口定义示例

> 源文件: [UserInterface.php](https://raw.githubusercontent.com/EellyDev/eelly-sdk-php/master/src/SDK/User/Service/UserInterface.php)

> 生成的文档: [user service doc](/user/user)

更详细的说明将在[sdk-php-wiki](https://github.com/EellyDev/eelly-sdk-php/wiki)进行说明

$moduleList
EOF;
        $this->view->markup = $this->parserMarkdown($markdown);
        $this->view->render('apidoc', 'home');
    }
}
