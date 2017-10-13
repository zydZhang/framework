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

namespace Eelly\Mvc\View\Engine;

use Eelly\Mvc\View;
use LightnCandy\LightnCandy;
use Phalcon\DiInterface;
use Phalcon\Mvc\View\Engine;
use Phalcon\Mvc\View\EngineInterface;
use Phalcon\Mvc\ViewBaseInterface;

/**
 * Phalcon\Mvc\View\Engine\Handlebars
 * Adapter to use Handlebars library as templating engine.
 */
class Handlebars extends Engine implements EngineInterface
{
    /**
     * @var \Handlebars_Engine
     */
    protected $mustache;

    /**
     * {@inheritdoc}
     *
     * @param ViewBaseInterface $view
     * @param DiInterface       $di
     */
    public function __construct(View $view, DiInterface $di = null)
    {
        parent::__construct($view, $di);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path
     * @param array  $params
     * @param bool   $mustClean
     */
    public function render($path, $params, $mustClean = false): void
    {
        if (!isset($params['content'])) {
            $params['content'] = $this->_view->getContent();
        }
        
        //编译文件命名
        $compiledFile = $path.'.php';
        
        //判断文件是否存在
        if (!file_exists($compiledFile)) {
            //获取模板文件的内容，然后处理
            $phpStr = LightnCandy::compile(file_get_contents($path), [
                'flags' => LightnCandy::FLAG_HANDLEBARSJS,
                'helpers' => [
                    'list' => function ($context, $options) {
                        $out = '';
                        $data = $options['data'];
                
                        foreach ($context as $idx => $cx) {
                            $data['index'] = $idx;
                            $out .= $options['fn']($cx, Array('data' => $data));
                        }
                
                        return $out;
                    },
                    'addIndex' => function ($num) {
                        return $num*1 + 1;
                    }
                ]
            ]);
            
            //生成编译文件
            file_put_contents($compiledFile, '<?php ' . $phpStr . '?>');
        }
        
        //引进编译文件
        $renderer = include($compiledFile);
        
        if ($mustClean) {
            $this->_view->setContent($renderer($params));
        } else {
            echo $renderer($params);
        }
    }
}
