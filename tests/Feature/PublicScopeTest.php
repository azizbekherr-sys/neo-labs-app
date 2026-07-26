<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_sixty_scope_urls_have_consistent_localized_seo_and_clean_content(): void
    {
        $products = collect(range(1, 9))->map(function (int $index) {
            return Product::create([
                'name' => "Mahsulot {$index}",
                'name_uz' => "Mahsulot {$index}",
                'name_ru' => "Продукт {$index}",
                'name_en' => "Product {$index}",
                'type_uz' => 'Kapsula',
                'type_ru' => 'Капсулы',
                'type_en' => 'Capsules',
                'description_uz' => '<p>**Toza** mahsulot tavsifi.</p>',
                'description_ru' => '<p>**Чистое** описание продукта.</p>',
                'description_en' => '<p>**Clean** product description.</p>',
                'status' => 'active',
                'is_featured' => $index <= 6,
            ]);
        });

        $articles = collect(range(1, 4))->map(function (int $index) {
            return Article::create([
                'title_uz' => "Maqola {$index}",
                'title_ru' => "Статья {$index}",
                'title_en' => "Article {$index}",
                'description_uz' => '<p>**Toza** maqola matni.</p>',
                'description_ru' => '<p>**Чистый** текст статьи.</p>',
                'description_en' => '<p>**Clean** article content.</p>',
                'author_name' => 'NEO-LABS Editorial Team',
                'author_slug' => 'neo-labs-editorial-team',
                'author_role_uz' => 'Tahririyat',
                'author_role_ru' => 'Редакция',
                'author_role_en' => 'Editorial team',
            ]);
        });

        $paths = [];
        foreach (['uz', 'ru', 'en'] as $locale) {
            foreach (['', 'catalog', 'manufacturing', 'news', 'about', 'contacts'] as $static) {
                $paths[] = '/' . $locale . ($static ? '/' . $static : '');
            }
            foreach ($products as $product) {
                $name = $product->{'name_' . $locale};
                $paths[] = "/{$locale}/product/{$product->id}/" . Str::slug($name);
            }
            foreach ($articles as $article) {
                $title = $article->{'title_' . $locale};
                $paths[] = "/{$locale}/news/{$article->id}/" . Str::slug($title);
            }
            $paths[] = "/{$locale}/authors/neo-labs-editorial-team";
        }

        $this->assertCount(60, $paths);
        foreach ($paths as $path) {
            $response = $this->get($path);
            $response->assertOk();
            $html = $response->getContent();
            $locale = explode('/', trim($path, '/'))[0];
            $this->assertStringContainsString('<html lang="' . $locale . '">', $html, $path);
            $this->assertSame(1, preg_match_all('/<h1(?:\s[^>]*)?>/i', $html), "H1 count: {$path}");
            $this->assertSame(1, substr_count($html, '<link rel="canonical"'), "Canonical count: {$path}");
            $this->assertStringNotContainsString('?q=', $this->canonicalFrom($html), $path);
            foreach (['uz-UZ', 'ru-UZ', 'en-UZ', 'x-default'] as $hreflang) {
                $this->assertStringContainsString('hreflang="' . $hreflang . '"', $html, "Missing {$hreflang}: {$path}");
            }
            $this->assertStringNotContainsString('href="#"', $html, $path);
            $this->assertDoesNotMatchRegularExpression('/(?:\*\*|^#{1,6}\s)/m', $html, $path);
        }

        $sitemap = $this->get('/sitemap.xml');
        $sitemap->assertOk();
        $this->assertSame(60, substr_count($sitemap->getContent(), '<url>'));
        $this->assertSame(60, substr_count($sitemap->getContent(), '<loc>'));
    }

    public function test_public_contact_form_validation_has_no_external_side_effect(): void
    {
        \Illuminate\Support\Facades\Http::fake();

        $this->from('/uz/contacts')->post('/contact/send', [
            'form_context' => 'general',
            'name' => '',
            'phone' => 'x',
            'message' => '',
            'website' => 'bot.example',
        ])->assertSessionHasErrors(['name', 'message', 'website']);

        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    private function canonicalFrom(string $html): string
    {
        preg_match('/<link rel="canonical" href="([^"]+)"/', $html, $match);
        return $match[1] ?? '';
    }
}
