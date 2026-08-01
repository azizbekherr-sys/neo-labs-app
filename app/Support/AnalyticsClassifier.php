<?php

namespace App\Support;

use Illuminate\Support\Str;

class AnalyticsClassifier
{
    public static function isBot(string $userAgent): bool
    {
        return $userAgent === '' || Str::contains(strtolower($userAgent), [
            'bot', 'crawler', 'spider', 'slurp', 'bingpreview', 'facebookexternalhit',
            'headlesschrome', 'lighthouse', 'pagespeed', 'curl/', 'wget/', 'python-requests',
        ]);
    }

    public static function device(string $userAgent, ?int $screenWidth = null): string
    {
        $ua = strtolower($userAgent);
        if (Str::contains($ua, ['ipad', 'tablet', 'kindle']) || Str::contains($ua, 'android') && !Str::contains($ua, 'mobile')) {
            return 'tablet';
        }
        if (Str::contains($ua, ['mobile', 'iphone', 'ipod', 'android'])) {
            return 'mobile';
        }
        if ($screenWidth && $screenWidth < 768) {
            return 'mobile';
        }
        return 'desktop';
    }

    public static function browser(string $userAgent): string
    {
        $map = [
            'Edge' => ['edg/', 'edge/'],
            'Opera' => ['opr/', 'opera'],
            'Samsung Internet' => ['samsungbrowser'],
            'Firefox' => ['firefox/', 'fxios/'],
            'Chrome' => ['chrome/', 'crios/'],
            'Safari' => ['safari/'],
        ];
        $ua = strtolower($userAgent);
        foreach ($map as $name => $needles) {
            if (Str::contains($ua, $needles)) return $name;
        }
        return 'Other';
    }

    public static function operatingSystem(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        if (Str::contains($ua, ['iphone', 'ipad', 'ipod'])) return 'iOS';
        if (Str::contains($ua, 'android')) return 'Android';
        if (Str::contains($ua, ['windows nt', 'windows phone'])) return 'Windows';
        if (Str::contains($ua, ['macintosh', 'mac os x'])) return 'macOS';
        if (Str::contains($ua, ['linux', 'x11'])) return 'Linux';
        return 'Other';
    }

    public static function channel(?string $referrerHost, ?string $source, ?string $medium): string
    {
        $host = strtolower((string) $referrerHost);
        $source = strtolower((string) $source);
        $medium = strtolower((string) $medium);
        $haystack = $host . ' ' . $source;

        if (Str::contains($medium, ['cpc', 'ppc', 'paid', 'display', 'ads'])) return 'paid';
        if (Str::contains($medium, ['email', 'newsletter'])) return 'email';
        if (Str::contains($medium, ['social', 'social-network']) || Str::contains($haystack, [
            'facebook', 'instagram', 'tiktok', 'linkedin', 'youtube', 'telegram', 't.me', 'twitter', 'x.com',
        ])) return 'social';
        if (Str::contains($haystack, [
            'chatgpt', 'openai', 'perplexity', 'copilot', 'gemini.google', 'claude.ai',
            'anthropic', 'you.com', 'grok.com', 'mistral.ai',
        ])) return 'ai';
        if (Str::contains($haystack, ['google.', 'bing.', 'yandex.', 'yahoo.', 'duckduckgo.'])) return 'organic';
        if ($source !== '') return 'campaign';
        if ($host !== '') return 'referral';
        return 'direct';
    }

    /** @return array{route_name:string,page_type:string,content_id:int|null,locale:string|null} */
    public static function page(string $path): array
    {
        preg_match('#^/(uz|ru|en)(?:/|$)#', $path, $localeMatch);
        $locale = $localeMatch[1] ?? null;

        if (preg_match('#^/(?:uz|ru|en)/product/(\d+)(?:/|$)#', $path, $match)) {
            return ['route_name' => 'product.show', 'page_type' => 'product', 'content_id' => (int) $match[1], 'locale' => $locale];
        }
        if (preg_match('#^/(?:uz|ru|en)/news/(\d+)(?:/|$)#', $path, $match)) {
            return ['route_name' => 'articles.show', 'page_type' => 'article', 'content_id' => (int) $match[1], 'locale' => $locale];
        }
        if (preg_match('#^/(?:uz|ru|en)/certificates/(\d+)(?:/|$)#', $path, $match)) {
            return ['route_name' => 'certificates.show', 'page_type' => 'certificate', 'content_id' => (int) $match[1], 'locale' => $locale];
        }

        $relative = preg_replace('#^/(?:uz|ru|en)#', '', $path) ?: '/';
        $pages = [
            '/' => ['home', 'home'], '/catalog' => ['catalog', 'catalog'],
            '/news' => ['articles', 'articles'], '/manufacturing' => ['manufacturing', 'manufacturing'],
            '/contacts' => ['contacts', 'contacts'], '/about' => ['about', 'about'],
            '/certificates' => ['certificates', 'certificates'], '/production' => ['production', 'production'],
            '/company-facts' => ['company-facts', 'company_facts'], '/editorial-policy' => ['editorial-policy', 'policy'],
            '/privacy' => ['privacy', 'policy'],
        ];
        [$route, $type] = $pages[$relative] ?? ['page', 'page'];
        return ['route_name' => $route, 'page_type' => $type, 'content_id' => null, 'locale' => $locale];
    }
}
