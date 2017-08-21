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
        //dd($reflectionMethod->getParameters());
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
        $doc = $reflectionMethod->getDocComment();
        $this->annotations->delete($reflectionMethod->class);
        $annotations = $this->annotations->getMethod(
            $reflectionMethod->class,
            $reflectionMethod->name
        );

        $arguments = $annotations->get('requestExample')->getArgument(0);
        $requestExample = '';
        if (is_array($arguments)) {
            foreach ($arguments as $key => $value) {
                $requestExample .= $key.':'.$value.PHP_EOL;
            }
        }
        $arguments = $annotations->get('returnExample')->getArgument(0);
        $returnExample = \GuzzleHttp\json_encode(['data' => $arguments], JSON_PRETTY_PRINT);
        $markdown = <<<EOF
```
    $doc
```
## $methodStr

## Request example

```
$requestExample
```
## Return example

```json
$returnExample    
```
EOF;
        $this->echoMarkdownHtml($markdown);
    }
}
