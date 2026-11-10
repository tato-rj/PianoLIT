<?php

namespace App\Services\Blog;

/** Browser presentation only; saved editorial HTML and mobile output stay unchanged. */
class ArticleContent
{
    public function render(?string $content): string
    {
        $content = $content ?? '';
        if (stripos($content, '<a') === false) return $content;

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'.$content.'</body></html>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $links = (new \DOMXPath($document))->query('//a[@href]');
        if ($links->length === 0) return $content;

        foreach ($links as $link) {
            $link->setAttribute('target', '_blank');
            $relations = preg_split('/\s+/', strtolower(trim($link->getAttribute('rel'))), -1, PREG_SPLIT_NO_EMPTY);
            $relations = array_diff($relations, ['opener']);
            $link->setAttribute('rel', implode(' ', array_unique(array_merge($relations, ['noopener', 'noreferrer']))));
        }

        $html = '';
        foreach ($document->getElementsByTagName('body')->item(0)->childNodes as $node) {
            $html .= $document->saveHTML($node);
        }
        return $html;
    }
}
