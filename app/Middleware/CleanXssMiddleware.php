<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CleanXssMiddleware implements MiddlewareInterface
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $post = $request->getParsedBody();
        foreach ($post as $k => $v) {
            if (!is_string($k) || !is_string($v)) {
                continue;
            }
            $post[$k] = $this->clean_xss($v);
        }
        $request = $request->withParsedBody($post);

        $query = $request->getQueryParams();
        foreach ($query as $k => $v) {
            if (!is_string($k) || !is_string($v)) {
                continue;
            }
            $query[$k] = $this->clean_xss($v);
        }
        $request = $request->withQueryParams($query);

        return $handler->handle($request);
    }

    private function clean_xss(?string $str)
    {
        if (empty($str)) {
            return $str;
        }

        $search = [
            '@<script[^>]*?>.*?</script>@si',
            '@<style[^>]*?>.*?</style>@siU',
            '@<![\s\S]*?--[ \t\n\r]*>@',
        ];
        $tagArray = [
            'a',
            'abbr',
            'acronym',
            'address',
            'applet',
            'area',
            'article',
            'em',
            'aside',
            'audio',
            'b',
            'base',
            'basefont',
            'bdi',
            'bdo',
            'big',
            'blockquote',
            'body',
            'br',
            'br/',
            'button',
            'canvas',
            'caption',
            'center',
            'cite',
            'code',
            'col',
            'colgroup',
            'command',
            'datalist',
            'dd',
            'del',
            'details',
            'dfn',
            'dialog',
            'dir',
            'div',
            'dl',
            'dt',
            'embed',
            'fieldset',
            'figcaption',
            'figure',
            'font',
            'footer',
            'form',
            'frame',
            'frameset',
            'h1',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'head',
            'header',
            'hr',
            'html',
            'i',
            'iframe',
            'img',
            'input',
            'ins',
            'kbd',
            'keygen',
            'label',
            'legend',
            'li',
            'link',
            'map',
            'menu',
            'meta',
            'meter',
            'nav',
            'noframes',
            'noscript',
            'object',
            'ol',
            'optgroup',
            'option',
            'output',
            'p',
            'param',
            'pre',
            'progress',
            'q',
            'rp',
            'rt',
            'ruby',
            's',
            'samp',
            'script',
            'section',
            'select',
            'small',
            'source',
            'span',
            'strike',
            'strong',
            'style',
            'sub',
            'summary',
            'sup',
            'table',
            'tbody',
            'td',
            'textarea',
            'tfoot',
            'th',
            'thead',
            'time',
            'title',
            'tr',
            'track',
            'tt',
            'u',
            'ul',
            'var',
            'video',
            'wbr',
            'image',
        ];

        foreach ($tagArray as $key => $value) {
            $search[] = '@<' . $value . '( +[^<>]*?>|>)@si';
            $search[] = '@</' . $value . '( +[^<>]*?>|>)@si';
        }
        $str = html_entity_decode($str, ENT_QUOTES);
        return preg_replace($search, '', $str);
    }
}
