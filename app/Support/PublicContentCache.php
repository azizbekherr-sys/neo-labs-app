<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Certificate;
use App\Models\CompanyFact;
use App\Models\Partner;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class PublicContentCache
{
    public const TTL = 300;

    public static function registerInvalidation(): void
    {
        foreach ([Product::class, Partner::class, Article::class, Certificate::class, CompanyFact::class] as $model) {
            $model::saved(fn () => self::flushFor($model));
            $model::deleted(fn () => self::flushFor($model));
        }
    }

    public static function flushFor(string $model): void
    {
        $keys = match ($model) {
            Product::class => ['public.home.products', 'seo.sitemap.index', 'seo.sitemap.products'],
            Partner::class => ['public.home.partners', 'public.manufacturing.partners'],
            Article::class => ['public.home.articles', 'seo.sitemap.index', 'seo.sitemap.articles'],
            Certificate::class => [
                'public.manufacturing.certificates',
                'public.certificates',
                'seo.sitemap.index',
                'seo.sitemap.pages.uz',
                'seo.sitemap.pages.ru',
                'seo.sitemap.pages.en',
                'seo.certifications.uz',
                'seo.certifications.ru',
                'seo.certifications.en',
            ],
            CompanyFact::class => ['public.company-facts'],
            default => [],
        };

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
