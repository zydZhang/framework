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

use Eelly\Annotations\Adapter\AdapterInterface;
use GuzzleHttp\json_encode;
use ReflectionClass;

/**
 * Class ApiDocumentShow.
 */
class ApiDocumentShow extends AbstractDocumentShow implements DocumentShowInterface
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $method;

    public function __construct(string $class, string $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    public function display(): void
    {
        $reflectionClass = new ReflectionClass($this->class);
        $interfaces = $reflectionClass->getInterfaces();
        $interface = array_pop($interfaces);
        $reflectionMethod = $interface->getMethod($this->method);
        $methodStr = 'function '.$reflectionMethod->getName().'(';
        foreach ($reflectionMethod->getParameters() as $key => $value) {
            if (0 != $key) {
                $methodStr .= ', ';
            }
            $methodStr .= $value->getType().' '.$value->getName();
            if ($value->isDefaultValueAvailable()) {
                $defaultValue = $value->getDefaultValue();
                if (null === $defaultValue) {
                    $defaultValue = 'null';
                }
                $methodStr .= ' = '.$defaultValue;
            }
        }
        $methodStr .= ')';
        $docComment = $reflectionMethod->getDocComment();
        $factory = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
        $docblock = $factory->create($docComment);
        $summary = $docblock->getSummary();
        $description = $docblock->getDescription();
        $params = '';
        foreach ($docblock->getTagsByName('param') as $item) {
            $params .= $item->getVariableName().'|'.$item->getDescription().PHP_EOL;
        }
        if ($this->annotations instanceof AdapterInterface) {
            $this->annotations->delete($reflectionMethod->class);
        }
        $annotations = $this->annotations->getMethod(
            $reflectionMethod->class,
            $reflectionMethod->name
        );

        $arguments = $annotations->get('requestExample')->getArgument(0);
        $requestExample = '';
        if (is_array($arguments)) {
            foreach ($arguments as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                $requestExample .= $key.':'.$value.PHP_EOL;
            }
        }
        $arguments = $annotations->get('returnExample')->getArgument(0);
        $returnExample = json_encode(['data' => $arguments], JSON_PRETTY_PRINT);
        $markdown = <<<EOF
## $summary

$description

### 参数

参数名|说明
------|-----
$params

### 接口原型
$methodStr

### 请求示例

```
$requestExample
```
### 返回示例

```json
$returnExample    
```
EOF;
        $this->echoMarkdownHtml($markdown);
    }
}
