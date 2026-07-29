<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Certificate;
use App\Models\Product;
use App\Support\Seo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SitemapController extends Controller
{
    public function __invoke()
    {
        return $this->index();
    }

    public function index()
    {
        $sitemaps = Cache::remember('seo.sitemap.index', 300, function () {
            $pageLastmod = collect([
                $this->latestStaticTimestamp(),
                $this->timestamp(Certificate::query()->where('is_published', true)->max('updated_at')),
            ])->filter()->sort()->last();
            $productLastmod = $this->timestamp(Product::query()->where('status', 'active')->max('updated_at'));
            $articleLastmod = $this->timestamp(Article::query()->max('updated_at'));

            $items = [];
            foreach ($this->locales() as $locale) {
                $items[] = [
                    'loc' => Seo::absolute("/sitemaps/pages-{$locale}.xml"),
                    'lastmod' => $pageLastmod,
                ];
            }
            $items[] = ['loc' => Seo::absolute('/sitemaps/products.xml'), 'lastmod' => $productLastmod];
            $items[] = ['loc' => Seo::absolute('/sitemaps/articles.xml'), 'lastmod' => $articleLastmod];

            return $items;
        });

        return $this->xml('seo.sitemap-index', compact('sitemaps'));
    }

    public function pages(string $locale)
    {
        abort_unless(in_array($locale, $this->locales(), true), 404);

        $urls = Cache::remember("seo.sitemap.pages.{$locale}", 300, function () use ($locale) {
            $urls = collect($this->staticPages())->map(function (array $page, string $path) use ($locale) {
                $localizedPath = $locale . ($path !== '' ? '/' . $path : '');
                $lastmod = is_file($page['view']) ? date(DATE_ATOM, filemtime($page['view'])) : null;

                return $this->entry($localizedPath, $lastmod, $page['image'] ?? null, null);
            })->values();

            Certificate::query()
                ->where('is_published', true)
                ->orderBy('id')
                ->each(function (Certificate $certificate) use ($locale, $urls) {
                    $urls->push($this->entry(
                        $this->certificatePath($certificate, $locale),
                        optional($certificate->updated_at)->toAtomString(),
                        config('seo.default_image'),
                        $certificate
                    ));
                });

            return $urls->all();
        });

        return $this->xml('seo.sitemap', compact('urls'));
    }

    public function products()
    {
        $urls = Cache::remember('seo.sitemap.products', 300, function () {
            $urls = [];
            Product::query()->where('status', 'active')->orderBy('id')->each(function (Product $product) use (&$urls) {
                foreach ($this->locales() as $locale) {
                    $path = $this->productPath($product, $locale);
                    $image = collect($product->images ?: [$product->image])->filter()->first();
                    $urls[] = $this->entry($path, optional($product->updated_at)->toAtomString(), $image, $product);
                }
            });
            return $urls;
        });

        return $this->xml('seo.sitemap', compact('urls'));
    }

    public function articles()
    {
        $urls = Cache::remember('seo.sitemap.articles', 300, function () {
            $urls = [];
            Article::query()->orderBy('id')->each(function (Article $article) use (&$urls) {
                foreach ($this->locales() as $locale) {
                    $urls[] = $this->entry(
                        $this->articlePath($article, $locale),
                        optional($article->updated_at)->toAtomString(),
                        $article->photo,
                        $article
                    );
                }
            });

            Article::query()
                ->whereNotNull('author_slug')
                ->where('author_slug', '!=', '')
                ->select('author_slug', 'updated_at')
                ->get()
                ->groupBy('author_slug')
                ->each(function ($articles, $slug) use (&$urls) {
                    $lastModified = optional($articles->sortByDesc('updated_at')->first()->updated_at)->toAtomString();
                    foreach ($this->locales() as $locale) {
                        $urls[] = $this->entry(
                            "{$locale}/authors/{$slug}",
                            $lastModified,
                            config('seo.default_image'),
                            null
                        );
                    }
                });

            return $urls;
        });

        return $this->xml('seo.sitemap', compact('urls'));
    }

    private function staticPages(): array
    {
        return [
            '' => ['view' => resource_path('views/welcome.blade.php'), 'image' => '/img/neo-labs-og.jpg'],
            'catalog' => ['view' => resource_path('views/pages/products.blade.php'), 'image' => '/img/neo-labs-products.webp'],
            'manufacturing' => ['view' => resource_path('views/pages/manufacturing.blade.php'), 'image' => '/img/neo-labs-contract-manufacturing.webp'],
            'production' => ['view' => resource_path('views/pages/production.blade.php'), 'image' => '/img/neo-labs-contract-manufacturing.webp'],
            'about' => ['view' => resource_path('views/pages/about.blade.php'), 'image' => '/img/neo-labs-about.webp'],
            'contacts' => ['view' => resource_path('views/pages/contacts.blade.php'), 'image' => '/img/neo-labs-contacts.webp'],
            'news' => ['view' => resource_path('views/pages/articles.blade.php'), 'image' => '/img/neo-labs-products.webp'],
            'certificates' => ['view' => resource_path('views/pages/certificates.blade.php'), 'image' => '/img/neo-labs-og.jpg'],
            'company-facts' => ['view' => resource_path('views/pages/company-facts.blade.php'), 'image' => '/img/neo-labs-og.jpg'],
            'editorial-policy' => ['view' => resource_path('views/pages/editorial-policy.blade.php'), 'image' => '/img/neo-labs-og.jpg'],
            'privacy' => ['view' => resource_path('views/pages/privacy.blade.php'), 'image' => '/img/neo-labs-og.jpg'],
        ];
    }

    private function entry(string $path, ?string $lastmod, ?string $image, $model): array
    {
        $segments = explode('/', trim($path, '/'));
        array_shift($segments);
        $suffix = implode('/', $segments);
        $alternates = [];

        foreach ($this->locales() as $locale) {
            if ($model instanceof Product) {
                $alternatePath = $this->productPath($model, $locale);
            } elseif ($model instanceof Article) {
                $alternatePath = $this->articlePath($model, $locale);
            } elseif ($model instanceof Certificate) {
                $alternatePath = $this->certificatePath($model, $locale);
            } else {
                $alternatePath = $locale . ($suffix ? '/' . $suffix : '');
            }
            $alternates[$locale . '-UZ'] = Seo::absolute($alternatePath);
        }

        $imageUrl = null;
        if ($image) {
            $imageUrl = Str::startsWith($image, ['http://', 'https://'])
                ? $image
                : Seo::absolute($image);
        }

        return [
            'loc' => Seo::absolute($path),
            'lastmod' => $lastmod,
            'image' => $imageUrl,
            'alternates' => $alternates,
        ];
    }

    private function productPath(Product $product, string $locale): string
    {
        $name = $product->{'name_' . $locale} ?: $product->name;
        return "{$locale}/product/{$product->id}/" . Str::slug($name ?: "product-{$product->id}");
    }

    private function articlePath(Article $article, string $locale): string
    {
        $title = $article->{'title_' . $locale};
        return "{$locale}/news/{$article->id}/" . Str::slug($title ?: "news-{$article->id}");
    }

    private function certificatePath(Certificate $certificate, string $locale): string
    {
        $name = $certificate->{'name_' . $locale} ?: $certificate->name_ru;
        return "{$locale}/certificates/{$certificate->id}/" . Str::slug($name ?: "certificate-{$certificate->id}");
    }

    private function locales(): array
    {
        return config('seo.locales', ['uz', 'ru', 'en']);
    }

    private function latestStaticTimestamp(): ?string
    {
        $timestamps = collect($this->staticPages())
            ->pluck('view')
            ->filter(fn ($path) => is_file($path))
            ->map(fn ($path) => filemtime($path))
            ->filter();

        return $timestamps->isEmpty() ? null : date(DATE_ATOM, $timestamps->max());
    }

    private function timestamp($value): ?string
    {
        return $value ? Carbon::parse($value)->toAtomString() : null;
    }

    private function xml(string $view, array $data)
    {
        return response()->view($view, $data, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=300, s-maxage=3600');
    }
}
