<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_renders_only_available_sections_and_informational_cta(): void
    {
        $product = $this->product([
            'short_description_uz' => 'Qisqa va aniq mahsulot tavsifi.',
            'composition_uz' => null,
            'warnings_uz' => null,
            'sales_mode' => 'informational',
        ]);

        $this->get($this->url($product))
            ->assertOk()
            ->assertSee('<h1 id="product-title">Sinov mahsuloti</h1>', false)
            ->assertSee('Mahsulot bo‘yicha maslahat olish')
            ->assertDontSee('id="product-composition-title"', false)
            ->assertDontSee('id="product-warnings-title"', false);
    }

    public function test_faq_uses_current_locale_and_adds_faq_schema(): void
    {
        $product = $this->product([
            'faqs_uz' => [['question' => 'Qanday qo‘llanadi?', 'answer' => 'Yo‘riqnomaga muvofiq.']],
            'faqs_ru' => [['question' => 'Как применять?', 'answer' => 'По инструкции.']],
        ]);

        $response = $this->get($this->url($product));
        $response->assertOk()
            ->assertSee('Qanday qo‘llanadi?')
            ->assertDontSee('Как применять?');

        $graph = $this->schemaGraph($response->getContent());
        $faq = collect($graph)->firstWhere('@type', 'FAQPage');
        $this->assertNotNull($faq);
        $this->assertSame('Qanday qo‘llanadi?', $faq['mainEntity'][0]['name']);
    }

    public function test_all_sales_modes_render_their_single_dominant_cta(): void
    {
        $informational = $this->product(['name' => 'Info', 'name_uz' => 'Info', 'sales_mode' => 'informational']);
        $external = $this->product([
            'name' => 'External',
            'name_uz' => 'External',
            'sales_mode' => 'external',
            'external_purchase_url' => 'https://shop.example/product',
        ]);
        $direct = $this->product([
            'name' => 'Direct',
            'name_uz' => 'Direct',
            'sales_mode' => 'direct',
            'price' => 125000,
            'currency' => 'UZS',
            'stock_status' => 'in_stock',
        ]);

        $this->get($this->url($informational))->assertOk()->assertSee('Mahsulot bo‘yicha maslahat olish');
        $this->get($this->url($external))->assertOk()->assertSee('Qayerdan sotib olish')->assertSee('https://shop.example/product');
        $this->get($this->url($direct))->assertOk()->assertSee('Sotib olish')->assertSee('125 000 UZS')->assertSee('Mavjud');
    }

    public function test_draft_and_paused_products_are_not_public(): void
    {
        foreach (['draft', 'paused'] as $status) {
            $product = $this->product(['name' => $status, 'name_uz' => $status, 'status' => $status]);
            $this->get($this->url($product))->assertNotFound();
        }
    }

    public function test_related_products_prefer_manual_then_category_and_never_include_current_product(): void
    {
        $category = ProductCategory::where('slug', 'capsules')->firstOrFail();
        $current = $this->product(['name' => 'Current', 'name_uz' => 'Current', 'category_id' => $category->id]);
        $manual = $this->product(['name' => 'Manual', 'name_uz' => 'Manual']);
        $sameCategory = $this->product(['name' => 'Category', 'name_uz' => 'Category', 'category_id' => $category->id]);
        $current->relatedProducts()->sync([$manual->id, $current->id]);

        $response = $this->get($this->url($current))->assertOk();
        $response->assertSee('Manual')->assertSee('Category');
        $relatedHtml = $this->between($response->getContent(), '<section class="related-section"', '</section>');
        $this->assertStringNotContainsString('Current', $relatedHtml);
    }

    public function test_seo_fallback_and_manual_override_are_respected(): void
    {
        $fallback = $this->product([
            'short_description_uz' => str_repeat('Aniq tavsif ', 20),
            'seo_override' => false,
            'seo_title_uz' => 'Ishlatilmasligi kerak',
        ]);
        $this->get($this->url($fallback))
            ->assertOk()
            ->assertSee('<title>Sinov mahsuloti — NEO-LABS</title>', false)
            ->assertDontSee('Ishlatilmasligi kerak');

        $manual = $this->product([
            'name' => 'Manual SEO',
            'name_uz' => 'Manual SEO',
            'seo_override' => true,
            'seo_title_uz' => 'Maxsus SEO sarlavha',
            'meta_description_uz' => 'Maxsus meta tavsif.',
        ]);
        $this->get($this->url($manual))
            ->assertOk()
            ->assertSee('<title>Maxsus SEO sarlavha — NEO-LABS</title>', false)
            ->assertSee('content="Maxsus meta tavsif."', false);
    }

    public function test_product_schema_contains_offer_only_when_real_direct_price_exists(): void
    {
        $informational = $this->product(['price' => 99000, 'sales_mode' => 'informational']);
        $infoNode = collect($this->schemaGraph($this->get($this->url($informational))->getContent()))->firstWhere('@type', 'Product');
        $this->assertArrayNotHasKey('offers', $infoNode);

        $direct = $this->product([
            'name' => 'Direct schema',
            'name_uz' => 'Direct schema',
            'sales_mode' => 'direct',
            'price' => 99000,
            'currency' => 'UZS',
            'stock_status' => 'in_stock',
            'sku' => 'SKU-99',
            'barcode' => '1234567890123',
        ]);
        $directNode = collect($this->schemaGraph($this->get($this->url($direct))->getContent()))->firstWhere('@type', 'Product');
        $this->assertSame('99000.00', $directNode['offers']['price']);
        $this->assertSame('UZS', $directNode['offers']['priceCurrency']);
        $this->assertSame('1234567890123', $directNode['gtin13']);
        $this->assertSame('SKU-99', $directNode['sku']);
    }

    public function test_legacy_product_uses_external_url_fallback_and_locale_content_fallback(): void
    {
        $product = $this->product([
            'name' => 'UZ fallback',
            'name_uz' => 'UZ fallback',
            'name_ru' => null,
            'name_en' => null,
            'sales_mode' => null,
            'external_purchase_url' => 'https://legacy.example/product',
        ]);

        $this->get('/en/product/'.$product->id.'/uz-fallback')
            ->assertOk()
            ->assertSee('UZ fallback')
            ->assertSee('Where to buy')
            ->assertSee('https://legacy.example/product');
    }

    public function test_admin_can_create_minimal_draft_without_ru_or_en(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('dashboard.products.store'), [
            'name_uz' => 'Minimal mahsulot',
            'status' => 'draft',
            'sales_mode' => 'informational',
        ])->assertRedirect(route('dashboard.products.index'));

        $this->assertDatabaseHas('products', [
            'name_uz' => 'Minimal mahsulot',
            'name_ru' => null,
            'name_en' => null,
            'status' => 'draft',
            'manufacturer' => 'NEO-LABS',
            'country_of_origin' => 'UZ',
        ]);
    }

    public function test_unapproved_medical_claim_cannot_be_published(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->from('/dashboard/products')->post(route('dashboard.products.store'), [
            'name_uz' => 'Review mahsulot',
            'short_description_uz' => 'Kasallikni davolaydi degan da’vo.',
            'status' => 'active',
            'sales_mode' => 'informational',
            'medical_review_status' => 'pending',
        ])->assertRedirect('/dashboard/products')
            ->assertSessionHasErrors('medical_review_status');

        $this->assertDatabaseMissing('products', ['name_uz' => 'Review mahsulot']);
    }

    public function test_admin_entry_redirects_without_exposing_an_error_page(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin')->assertRedirect(route('dashboard'));
    }

    private function product(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Sinov mahsuloti',
            'name_uz' => 'Sinov mahsuloti',
            'name_ru' => 'Тестовый продукт',
            'name_en' => 'Test product',
            'short_description_uz' => 'Mahsulot haqida qisqa ma’lumot.',
            'status' => 'active',
            'robots' => 'index,follow',
            'content_status' => 'complete',
        ], $attributes));
    }

    private function url(Product $product): string
    {
        return '/uz/product/'.$product->id.'/'.\Illuminate\Support\Str::slug($product->name_uz ?: $product->name);
    }

    private function schemaGraph(string $html): array
    {
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);
        $schema = json_decode($matches[1] ?? '{}', true);
        $this->assertIsArray($schema);

        return $schema['@graph'] ?? [];
    }

    private function between(string $value, string $start, string $end): string
    {
        $startPosition = strpos($value, $start);
        if ($startPosition === false) return '';
        $endPosition = strpos($value, $end, $startPosition);
        if ($endPosition === false) return substr($value, $startPosition);

        return substr($value, $startPosition, $endPosition - $startPosition);
    }
}
