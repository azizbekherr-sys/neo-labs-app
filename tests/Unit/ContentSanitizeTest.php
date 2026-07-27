<?php

namespace Tests\Unit;

use App\Support\Content;
use Tests\TestCase;

class ContentSanitizeTest extends TestCase
{
    /** Safe formatting must be preserved (headings, lists, links, strong). */
    public function test_preserves_safe_formatting(): void
    {
        $html = Content::sanitize(
            '<h2>Заголовок</h2><p>Текст <strong>жирный</strong> и '
            . '<a href="https://ok.com">ссылка</a>.</p><ul><li>один</li><li>два</li></ul>'
        );

        $this->assertStringContainsString('<h2>Заголовок</h2>', $html);
        $this->assertStringContainsString('<ul><li>один</li>', $html);
        $this->assertStringContainsString('<strong>жирный</strong>', $html);
        $this->assertStringContainsString('href="https://ok.com"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    /** Images and tables from CMS content are allowed but scrubbed. */
    public function test_allows_images_and_tables_but_scrubs_them(): void
    {
        $html = Content::sanitize(
            '<img src="https://cdn.example.com/pic.png" onerror="alert(1)" alt="pic">'
            . '<table><tr><th onclick="x">A</th></tr><tr><td>B</td></tr></table>'
        );

        $this->assertStringContainsString('src="https://cdn.example.com/pic.png"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<td>B</td>', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    /** Dangerous HTML must never survive sanitization. */
    public function test_strips_dangerous_html(): void
    {
        $html = Content::sanitize(
            '<script>alert(1)</script>'
            . '<a href="javascript:alert(1)">xss</a>'
            . '<img src="javascript:alert(1)">'
            . '<iframe src="https://evil.com"></iframe>'
        );

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('<iframe', $html);
    }

    /** Bare http(s) URLs become safe external links (Uzum Market case). */
    public function test_autolinks_bare_urls_safely(): void
    {
        $url = 'https://uzum.uz/uz/product/magniy-b6-480mg-50-dona-antistress-1963732?skuId=6937964';
        $html = Content::sanitize('<p>Купить: ' . $url . ' сегодня.</p>');

        $this->assertStringContainsString('<a href="' . $url . '"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        // URL already inside an <a> must not be double-linked.
        $already = Content::sanitize('<a href="' . $url . '">buy</a>');
        $this->assertSame(1, substr_count($already, '<a '));
    }

    /** Empty content yields an empty string (template shows the fallback). */
    public function test_empty_content_returns_empty_string(): void
    {
        $this->assertSame('', Content::sanitize(''));
        $this->assertSame('', Content::sanitize(null));
    }
}
