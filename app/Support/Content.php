<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;

class Content
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'em', 'b', 'i', 'ul', 'ol', 'li',
        'h2', 'h3', 'h4', 'blockquote', 'a', 'img',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption',
    ];

    public static function localized($model, string $base, ?string $locale = null)
    {
        $locale = in_array($locale ?: app()->getLocale(), ['uz', 'ru', 'en'], true)
            ? ($locale ?: app()->getLocale())
            : 'ru';

        foreach ([$base . '_' . $locale, $base . '_ru', $base . '_uz', $base . '_en', $base] as $field) {
            $value = $model->{$field} ?? null;
            if ($value !== null && trim((string) $value) !== '') {
                return $value;
            }
        }

        return null;
    }

    public static function plain(?string $value): string
    {
        $value = self::normalizeSource((string) $value);
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    public static function excerpt(?string $value, int $limit = 200): string
    {
        $plain = self::plain($value);
        return $plain === '' ? '' : Str::limit($plain, $limit, '…');
    }

    public static function sanitize(?string $value): string
    {
        $source = self::normalizeSource((string) $value);
        if (trim($source) === '') {
            return '';
        }

        if ($source === strip_tags($source)) {
            $paragraphs = preg_split('/\R{2,}/u', trim($source)) ?: [];
            $source = implode('', array_map(function ($paragraph) {
                return '<p>' . nl2br(e(trim($paragraph)), false) . '</p>';
            }, array_filter($paragraphs, 'strlen')));
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="content-root">' . $source . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('content-root');
        if (!$root) {
            return e(self::plain($source));
        }

        self::sanitizeChildren($root);

        $html = '';
        foreach ($root->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }

        return trim($html);
    }

    public static function hasRawMarkdown(?string $value): bool
    {
        return preg_match('/(?:\*\*|^#{1,6}\s|__[^_]+__)/mu', (string) $value) === 1;
    }

    private static function normalizeSource(string $value): string
    {
        $value = preg_replace('/\*\*(.+?)\*\*/su', '<strong>$1</strong>', $value);
        $value = preg_replace('/^#{1,6}\s+(.+)$/mu', '<h3>$1</h3>', (string) $value);
        $value = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]\x{FE0F}?/u', '', (string) $value);
        $value = preg_replace('/<\/(strong|em|b|i)>(?=[\p{L}\p{N}])/u', '</$1> ', (string) $value);
        $value = preg_replace('/(?<=[\p{L}\p{N}])<(strong|em|b|i)>/u', ' <$1>', (string) $value);
        return trim((string) $value);
    }

    private static function sanitizeChildren(DOMNode $parent): void
    {
        for ($node = $parent->firstChild; $node !== null;) {
            $next = $node->nextSibling;

            if ($node->nodeType === XML_COMMENT_NODE) {
                $parent->removeChild($node);
                $node = $next;
                continue;
            }

            if ($node->nodeType === XML_TEXT_NODE) {
                $parentTag = $parent instanceof DOMElement ? strtolower($parent->tagName) : '';
                if ($parentTag !== 'a' && str_contains((string) $node->nodeValue, '://')) {
                    self::linkifyTextNode($node);
                }
                $node = $next;
                continue;
            }

            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form'], true)) {
                    $parent->removeChild($node);
                    $node = $next;
                    continue;
                }

                if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                    self::sanitizeChildren($node);
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);
                    $node = $next;
                    continue;
                }

                $href = $tag === 'a' ? trim($node->getAttribute('href')) : '';
                $imgSrc = $tag === 'img' ? trim($node->getAttribute('src')) : '';
                $imgAlt = $tag === 'img' ? (string) $node->getAttribute('alt') : '';
                while ($node->attributes && $node->attributes->length) {
                    $node->removeAttributeNode($node->attributes->item(0));
                }
                if ($tag === 'a' && preg_match('#^(https?://|/)#i', $href)) {
                    $node->setAttribute('href', $href);
                    $node->setAttribute('rel', 'noopener noreferrer');
                }
                if ($tag === 'img') {
                    if (preg_match('#^(https?://|/)#i', $imgSrc)) {
                        $node->setAttribute('src', $imgSrc);
                        $node->setAttribute('alt', $imgAlt);
                        $node->setAttribute('loading', 'lazy');
                        $node->setAttribute('decoding', 'async');
                    } else {
                        $parent->removeChild($node);
                        $node = $next;
                        continue;
                    }
                }

                self::sanitizeChildren($node);
            }

            $node = $next;
        }
    }

    /** Turn bare http(s) URLs inside a text node into safe external links. */
    private static function linkifyTextNode(DOMNode $node): void
    {
        $text = (string) $node->nodeValue;
        if (!preg_match('#https?://#u', $text)) {
            return;
        }

        $parts = preg_split(
            '#(https?://[^\s<>()"\']+[^\s<>()"\'.,;:!?])#u',
            $text,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );
        if (!$parts) {
            return;
        }

        $doc = $node->ownerDocument;
        $parent = $node->parentNode;
        if (!$doc || !$parent) {
            return;
        }

        $hasUrl = false;
        foreach ($parts as $part) {
            if (preg_match('#^https?://#', $part)) {
                $hasUrl = true;
                break;
            }
        }
        if (!$hasUrl) {
            return;
        }

        foreach ($parts as $part) {
            if (preg_match('#^https?://#', $part)) {
                $anchor = $doc->createElement('a');
                $anchor->setAttribute('href', $part);
                $anchor->setAttribute('rel', 'noopener noreferrer nofollow');
                $anchor->setAttribute('target', '_blank');
                $anchor->appendChild($doc->createTextNode($part));
                $parent->insertBefore($anchor, $node);
            } else {
                $parent->insertBefore($doc->createTextNode($part), $node);
            }
        }

        $parent->removeChild($node);
    }
}
