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
use Symfony\Component\Finder\Finder;

/**
 * Class ModuleDocumentShow.
 */
class ModuleDocumentShow extends AbstractDocumentShow implements DocumentShowInterface
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
        $docComment = $this->getDocComment($reflectionClass->getDocComment());
        $authorsStr = '';
        foreach ($docComment['authors'] as $item) {
            $authorsStr .= $item->getAuthorName().'|<'.$item->getEmail().'>'.PHP_EOL;
        }
        $finder = Finder::create()
            ->in(dirname($reflectionClass->getFileName()).'/Logic')
            ->files()
            ->name('*Logic.php');
        $interfaceList = '';
        $namespaceName = $reflectionClass->getNamespaceName();
        foreach ($finder as $item) {
            $serviceName = substr($item->getFilename(), 0, -9);
            $interfaceName = 'Eelly\\SDK\\'.$namespaceName.'\\Service\\'.$serviceName.'Interface';
            try {
                $reflectionClass = new ReflectionClass($interfaceName);
            } catch (\ReflectionException $e) {
                continue;
            }
            $docc = $this->getDocComment($reflectionClass->getDocComment());
            $interfaceList .= '- ['.$interfaceName.'](/'.lcfirst($namespaceName).'/'.lcfirst($serviceName).') '.$docc['summary'].PHP_EOL;
        }
        $markdown = <<<EOF
## {$docComment['summary']}
{$docComment['description']}
### 服务列表
$interfaceList
### 代码贡献
用户名|邮箱
------|-------
$authorsStr
EOF;
        $this->echoMarkdownHtml($markdown);
    }
}
