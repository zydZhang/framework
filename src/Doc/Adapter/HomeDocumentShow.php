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

/**
 * Class HomeDocumentShow.
 */
class HomeDocumentShow extends AbstractDocumentShow implements DocumentShowInterface
{
    public function display(): void
    {
        $moduleList = '';
        foreach ($this->config->modules as $module => $value) {
            $moduleList .= '- ['.$module.'](/'.$module.')'.PHP_EOL;
        }
        $markdown = <<<EOF
## 衣联网api开放文档

### 模块列表
$moduleList
EOF;
        $this->echoMarkdownHtml($markdown);
    }
}
