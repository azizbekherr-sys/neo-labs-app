<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Product;
use App\Support\Seo;
use Illuminate\Support\Str;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $urls = [];
        $static = [
            '' => resource_path('views/welcome.blade.php'),
            'catalog' => resource_path('views/pages/products.blade.php'),
            'manufacturing' => resource_path('views/pages/manufacturing.blade.php'),
            'about' => resource_path('views/pages/about.blade.php'),
            'contacts' => resource_path('views/pages/contacts.blade.php'),
            'news' => resource_path('views/pages/articles.blade.php'),
        ];
        $staticImages = [
            '' => '/img/neo-labs-og.jpg',
            'catalog' => '/img/neo-labs-products.webp',
            'manufacturing' => '/img/neo-labs-contract-manufacturing.webp',
            'about' => '/img/neo-labs-about.webp',
            'contacts' => '/img/neo-labs-contacts.webp',
            'news' => '/img/neo-labs-products.webp',
        ];

        foreach ($static as $path => $viewFile) {
            $lastmod = is_file($viewFile) ? date(DATE_ATOM, filemtime($viewFile)) : null;
            $this->addLocalized($urls, $path, $lastmod, $staticImages[$path] ?? config('seo.default_image'));
        }

        Product::query()->where('status', 'active')->orderBy('id')->each(function (Product $product) use (&$urls) {
            foreach (config('seo.locales') as $locale) {
                $name = $product->{'name_' . $locale} ?: $product->name;
                $path = $locale . '/product/' . $product->id . '/' . Str::slug($name ?: 'product-' . $product->id);
                $image = collect($product->images ?: [$product->image])->filter()->first();
                $urls[] = $this->entry($path, optional($product->updated_at)->toAtomString(), $image, $product, 'product');
            }
        });

        Article::query()->orderBy('id')->each(function (Article $article) use (&$urls) {
            foreach (config('seo.locales') as $locale) {
                $title = $article->{'title_' . $locale};
                $path = $locale . '/news/' . $article->id . '/' . Str::slug($title ?: 'news-' . $article->id);
                $urls[] = $this->entry($path, optional($article->updated_at)->toAtomString(), $article->photo, $article, 'article');
            }
        });

        Article::query()->whereNotNull('author_slug')->select('author_slug', 'updated_at')->get()
            ->groupBy('author_slug')->each(function ($articles, $slug) use (&$urls) {
                $lastModified = $articles->sortByDesc('updated_at')->first()->updated_at ?? null;
                $this->addLocalized($urls, 'authors/' . $slug, optional($lastModified)->toAtomString(), config('seo.default_image'));
            });

        return response()->view('seo.sitemap', compact('urls'), 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function addLocalized(array &$urls, string $path, ?string $lastmod = null, ?string $image = null): void
    {
        foreach (config('seo.locales') as $locale) {
            $localizedPath = $locale . ($path ? '/' . $path : '');
            $urls[] = $this->entry($localizedPath, $lastmod, $image, null, $path);
        }
    }

    private function entry(string $path, ?string $lastmod = null, ?string $image = null, $model = null, ?string $type = null): array
    {
        $segments = explode('/', trim($path, '/'));
        $locale = array_shift($segments);
        $suffix = implode('/', $segments);
        $alternates = [];
        foreach (config('seo.locales') as $alternateLocale) {
            if ($model instanceof Product) {
                $name = $model->{'name_' . $alternateLocale} ?: $model->name;
                $alternatePath = $alternateLocale . '/product/' . $model->id . '/' . Str::slug($name ?: 'product-' . $model->id);
            } elseif ($model instanceof Article) {
                $title = $model->{'title_' . $alternateLocale};
                $alternatePath = $alternateLocale . '/news/' . $model->id . '/' . Str::slug($title ?: 'news-' . $model->id);
            } else {
                $alternatePath = $alternateLocale . ($suffix ? '/' . $suffix : '');
            }
            $alternates[$alternateLocale . '-UZ'] = Seo::absolute($alternatePath);
        }

        return [
            'loc' => Seo::absolute($path),
            'lastmod' => $lastmod,
            'image' => $image ? Seo::absolute($image) : null,
            'alternates' => $alternates,
        ];
    }
}
