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
 * Class AbstractDocumentShow.
 */
abstract class AbstractDocumentShow extends Injectable
{
    protected function echoMarkdownHtml($markdown): void
    {
        $parser = new \Parsedown();
        $markup = $parser->text($markdown);
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/2.8.0/github-markdown.min.css">
        <style>
.markdown-body {
    box-sizing: border-box;
    min-width: 200px;
    max-width: 980px;
    margin: 0 auto;
    padding: 45px;
}
@media (max-width: 767px) {
    .markdown-body {
        padding: 15px;
    }
}
        </style>
    </head>
    <body><article class="markdown-body">$markup</article></body>
</html>
HTML;
    }
}
