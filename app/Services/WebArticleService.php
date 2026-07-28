<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Fetches a source article URL (server-side) and resolves a topic image.
 * Used by the admin "fill article from a link" feature.
 */
class WebArticleService
{
    private const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    /**
     * Fetch and extract readable text + metadata from a URL.
     *
     * @return array{title:?string,text:string,og_image:?string}
     */
    public function fetch(string $url): array
    {
        $resp = Http::withHeaders(['User-Agent' => self::UA])
            ->timeout(40)
            ->get($url);

        if (!$resp->successful()) {
            throw new \RuntimeException('Havolani ochib bo‘lmadi (HTTP ' . $resp->status() . ').');
        }

        $html = (string) $resp->body();

        return [
            'title' => $this->metaTitle($html),
            'text' => $this->readableText($html),
            'og_image' => $this->absolute($url, $this->ogImage($html)),
        ];
    }

    /**
     * Pick a topic image: Pexels (license-free) when configured, else the
     * source article's own OG image. Downloads into public/articles and
     * returns the relative path, or null.
     */
    public function resolveImage(?string $query, ?string $ogImage): ?string
    {
        $pexelsKey = (string) config('services.pexels.key');
        if ($pexelsKey !== '' && $query) {
            $src = $this->pexelsSearch($pexelsKey, $query);
            if ($src && ($path = $this->download($src))) {
                return $path;
            }
        }
        if ($ogImage && ($path = $this->download($ogImage))) {
            return $path;
        }
        return null;
    }

    // ------------------------------------------------------------- internals

    private function pexelsSearch(string $key, string $query): ?string
    {
        try {
            $resp = Http::withHeaders(['Authorization' => $key])
                ->timeout(25)
                ->get('https://api.pexels.com/v1/search', [
                    'query' => $query,
                    'per_page' => 1,
                    'orientation' => 'landscape',
                ]);
            if ($resp->successful()) {
                return $resp->json('photos.0.src.large') ?? $resp->json('photos.0.src.original');
            }
        } catch (\Throwable $e) {
            // fall through to og:image
        }
        return null;
    }

    private function download(string $url): ?string
    {
        try {
            $resp = Http::withHeaders(['User-Agent' => self::UA])->timeout(40)->get($url);
            if (!$resp->successful()) {
                return null;
            }
            $type = strtolower((string) $resp->header('Content-Type'));
            $ext = match (true) {
                str_contains($type, 'jpeg'), str_contains($type, 'jpg') => 'jpg',
                str_contains($type, 'png') => 'png',
                str_contains($type, 'webp') => 'webp',
                default => null,
            };
            $body = $resp->body();
            if ($ext === null || strlen($body) < 1024 || strlen($body) > 8 * 1024 * 1024) {
                return null; // not an image, too small, or too large
            }
            return media_store_binary($body, 'articles', $ext, $type);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function metaTitle(string $html): ?string
    {
        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) {
            return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return null;
    }

    private function ogImage(string $html): ?string
    {
        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/<article\b[^>]*>.*?<img[^>]+src=["\']([^"\']+)/is', $html, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function readableText(string $html): string
    {
        // Prefer <article> or <main>, else the whole document.
        $scope = $html;
        if (preg_match('/<article\b[^>]*>(.*?)<\/article>/is', $html, $m)) {
            $scope = $m[1];
        } elseif (preg_match('/<main\b[^>]*>(.*?)<\/main>/is', $html, $m)) {
            $scope = $m[1];
        }
        // Drop non-content tags.
        $scope = preg_replace('#<(script|style|nav|footer|header|form|noscript|svg)\b[^>]*>.*?</\1>#is', ' ', $scope);
        $text = html_entity_decode(strip_tags($scope), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        // Keep a large slice so the AI receives the full source article, not a
        // truncated summary. Long-form pieces can run well past 8k characters.
        return Str::limit($text, 40000, '');
    }

    private function absolute(string $base, ?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }
        $parts = parse_url($base);
        if (!isset($parts['scheme'], $parts['host'])) {
            return $url;
        }
        $origin = $parts['scheme'] . '://' . $parts['host'];
        return $origin . '/' . ltrim($url, '/');
    }
}
