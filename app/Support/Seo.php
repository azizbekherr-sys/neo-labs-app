<?php

namespace App\Support;

use App\Models\Certificate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Seo
{
    public static function baseUrl(): string
    {
        return rtrim((string) config('seo.base_url', 'https://neo-labs.uz'), '/');
    }

    public static function locale(): string
    {
        $locale = app()->getLocale();
        return in_array($locale, config('seo.locales', ['ru', 'uz', 'en']), true)
            ? $locale
            : (string) config('seo.default_locale', 'ru');
    }

    public static function absolute(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            $query = parse_url($url, PHP_URL_QUERY);
            return self::baseUrl() . self::normalizePath($path) . ($query ? '?' . $query : '');
        }

        return self::baseUrl() . self::normalizePath($url);
    }

    public static function route(string $name, array $parameters = []): string
    {
        $generated = route($name, $parameters, false);
        return self::absolute($generated);
    }

    public static function canonical(?string $override = null): string
    {
        if ($override) {
            return strtok((string) self::absolute($override), '?');
        }
        $url = self::baseUrl() . self::normalizePath(request()->getPathInfo());
        $page = (int) request()->query('page', 1);
        return $page > 1 ? $url . '?page=' . $page : $url;
    }

    public static function alternate(string $locale): string
    {
        $route = request()->route();
        if (!$route || !$route->getName()) {
            return self::baseUrl() . '/' . $locale;
        }

        // Route::view() stores internal defaults such as "view" and "status"
        // alongside real URI parameters. Passing those back to route() turns
        // them into query strings in hreflang URLs.
        $parameters = $route->parameters();
        if (method_exists($route, 'parameterNames')) {
            $parameters = array_intersect_key($parameters, array_flip($route->parameterNames()));
        } else {
            unset($parameters['view'], $parameters['data'], $parameters['status'], $parameters['headers']);
        }
        $parameters['locale'] = $locale;

        if ($route->getName() === 'product.show' && isset($parameters['product'])) {
            $product = $parameters['product'];
            $name = $product->{'name_' . $locale} ?: $product->name;
            $parameters['slug'] = Str::slug($name ?: 'product-' . $product->id);
        } elseif ($route->getName() === 'articles.show' && isset($parameters['article'])) {
            $article = $parameters['article'];
            $title = $article->{'title_' . $locale};
            $parameters['slug'] = Str::slug($title ?: 'news-' . $article->id);
        } elseif ($route->getName() === 'certificates.show' && isset($parameters['certificate'])) {
            $certificate = $parameters['certificate'];
            $name = $certificate->{'name_' . $locale} ?: $certificate->name_ru;
            $parameters['slug'] = Str::slug($name ?: 'certificate-' . $certificate->id);
        }

        $url = self::route($route->getName(), $parameters);
        $page = (int) request()->query('page', 1);
        return $page > 1 ? $url . '?page=' . $page : $url;
    }

    public static function organization(string $locale): array
    {
        $address = config('seo.address', []);
        $organization = [
            '@type' => ['Organization', 'LocalBusiness'],
            '@id' => self::baseUrl() . '/#organization',
            'name' => config('seo.site_name'),
            'alternateName' => config('seo.alternate_name'),
            'legalName' => config('seo.legal_name'),
            'url' => self::baseUrl(),
            'logo' => self::absolute(config('seo.logo')),
            'image' => self::absolute(config('seo.default_image')),
            'description' => config("seo.descriptions.{$locale}"),
            'foundingDate' => config('seo.founding_date'),
            'telephone' => config('seo.phone'),
            'email' => config('seo.email'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $address['street'] ?? null,
                'addressLocality' => $address['locality'][$locale] ?? null,
                'addressRegion' => $address['district'][$locale] ?? null,
                'postalCode' => $address['postal_code'] ?? null,
                'addressCountry' => $address['country_code'] ?? 'UZ',
            ],
            'areaServed' => ['@type' => 'Country', 'name' => $address['country'][$locale] ?? 'Uzbekistan'],
            'sameAs' => config('seo.social_profiles', []),
            'contactPoint' => [[
                '@type' => 'ContactPoint',
                'telephone' => config('seo.phone'),
                'email' => config('seo.email'),
                'contactType' => 'customer service',
                'areaServed' => 'UZ',
                'availableLanguage' => ['uz', 'ru', 'en'],
            ]],
            'knowsAbout' => [
                ['ru' => 'Производство биологически активных добавок', 'uz' => 'Biologik faol qo‘shimchalar ishlab chiqarish', 'en' => 'Dietary supplement manufacturing'][$locale],
                ['ru' => 'Контрактное производство', 'uz' => 'Kontrakt ishlab chiqarish', 'en' => 'Contract manufacturing'][$locale],
                ['ru' => 'Таблетки, капсулы, флаконы и саше', 'uz' => 'Tabletka, kapsula, flakon va sache qadoqlash', 'en' => 'Tablets, capsules, bottles and sachets'][$locale],
            ],
            'makesOffer' => [[
                '@type' => 'Offer',
                'itemOffered' => [
                    '@type' => 'Service',
                    'name' => ['ru' => 'Контрактное производство полного цикла', 'uz' => 'To‘liq sikldagi kontrakt ishlab chiqarish', 'en' => 'Full-cycle contract manufacturing'][$locale],
                ],
            ]],
            'hasCertification' => self::certifications($locale),
            'numberOfEmployees' => config('seo.employee_count') ? [
                '@type' => 'QuantitativeValue',
                'value' => (int) config('seo.employee_count'),
            ] : null,
            'taxID' => config('seo.tax_id'),
        ];

        return self::clean($organization);
    }

    public static function graph(string $title, string $description, string $canonical, array $breadcrumbs = [], array $extra = []): array
    {
        $locale = self::locale();
        $image = self::absolute(config('seo.default_image'));
        $graph = [
            self::organization($locale),
            [
                '@type' => 'WebSite',
                '@id' => self::baseUrl() . '/#website',
                'url' => self::baseUrl(),
                'name' => config('seo.site_name'),
                'inLanguage' => ['ru-UZ', 'uz-UZ', 'en-UZ'],
                'publisher' => ['@id' => self::baseUrl() . '/#organization'],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => self::baseUrl() . '/' . $locale . '/catalog?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@type' => 'WebPage',
                '@id' => $canonical . '#webpage',
                'url' => $canonical,
                'name' => $title,
                'description' => $description,
                'inLanguage' => $locale . '-UZ',
                'isPartOf' => ['@id' => self::baseUrl() . '/#website'],
                'primaryImageOfPage' => ['@id' => $image . '#image'],
            ],
            [
                '@type' => 'ImageObject',
                '@id' => $image . '#image',
                'url' => $image,
                'contentUrl' => $image,
                'caption' => config('seo.site_name'),
            ],
        ];

        if ($breadcrumbs) {
            $graph[] = [
                '@type' => 'BreadcrumbList',
                '@id' => $canonical . '#breadcrumb',
                'itemListElement' => array_map(function ($item, $index) {
                    return [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $item['name'],
                        'item' => self::absolute($item['url']),
                    ];
                }, array_values($breadcrumbs), array_keys(array_values($breadcrumbs))),
            ];
        }

        foreach ($extra as $node) {
            if ($node) {
                $graph[] = $node;
            }
        }

        return self::clean(['@context' => 'https://schema.org', '@graph' => $graph]);
    }

    public static function clean(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $item = self::clean($item);
            }
            if ($item === null || $item === '' || $item === []) {
                unset($value[$key]);
            } else {
                $value[$key] = $item;
            }
        }
        return $value;
    }

    private static function certifications(string $locale): array
    {
        return Cache::remember('seo.certifications.' . $locale, 3600, function () use ($locale) {
            // Schema::hasTable() performs a database round-trip. Keep it inside
            // the cache callback so ordinary public page renders do not query
            // the database just to build the shared Organization schema.
            if (!Schema::hasTable('certificates')) {
                return [];
            }

            return Certificate::query()->where('is_published', true)->orderBy('id')->get()->map(function (Certificate $certificate) use ($locale) {
                $name = $certificate->{'name_' . $locale} ?: $certificate->name_ru;
                return self::clean([
                    '@type' => 'Certification',
                    'name' => $name,
                    'identifier' => $certificate->number,
                    'issuedBy' => ($certificate->{'issuer_' . $locale} ?: $certificate->issuer_ru) ? [
                        '@type' => 'Organization',
                        'name' => $certificate->{'issuer_' . $locale} ?: $certificate->issuer_ru,
                    ] : null,
                    'validFrom' => optional($certificate->issued_at)->toDateString(),
                    'expires' => optional($certificate->expires_at)->toDateString(),
                    'url' => $certificate->verification_url ?: self::route('certificates.show', [
                        'locale' => $locale,
                        'certificate' => $certificate,
                        'slug' => Str::slug($name ?: 'certificate-' . $certificate->id),
                    ]),
                ]);
            })->all();
        });
    }

    private static function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        return $path;
    }
}
