<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private array $adminPages = [
        '/dashboard',
        '/dashboard/products',
        '/dashboard/articles',
        '/dashboard/partners',
        '/dashboard/documents',
        '/dashboard/messages',
    ];

    public function test_guests_are_redirected_from_every_admin_get_page(): void
    {
        foreach ($this->adminPages as $page) {
            $this->get($page)->assertRedirect('/login');
        }
    }

    public function test_authenticated_admin_can_open_every_management_page(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ($this->adminPages as $page) {
            $this->get($page)->assertOk()->assertSee('Asosiy kontentga o‘tish');
        }

        $this->get('/dashboard')
            ->assertDontSee('createProductModal')
            ->assertDontSee('ckeditor.com');
    }

    public function test_product_search_and_filters_narrow_results_and_strip_html(): void
    {
        $admin = User::factory()->create();
        Product::create([
            'name' => 'Vitamin C', 'name_uz' => 'Vitamin C', 'name_ru' => 'Витамин C', 'name_en' => 'Vitamin C',
            'barcode' => '478000001', 'description_uz' => '<p><strong>Immunitet</strong> uchun</p>',
            'status' => 'active', 'prescription' => false, 'content_status' => 'complete',
        ]);
        Product::create([
            'name' => 'Retsept mahsulot', 'name_uz' => 'Retsept mahsulot', 'name_ru' => 'Рецептурный', 'name_en' => 'Prescription',
            'barcode' => '478000002', 'status' => 'draft', 'prescription' => true, 'content_status' => 'incomplete',
        ]);

        $this->actingAs($admin)->get('/dashboard/products?q=Vitamin&status=active&prescription=0&content_status=complete')
            ->assertOk()
            ->assertSee('Vitamin C')
            ->assertDontSee('Retsept mahsulot')
            ->assertSee('Immunitet uchun')
            ->assertDontSee('<strong>Immunitet</strong>', false)
            ->assertSee('4 ta faol filtr');

        $this->actingAs($admin)->get('/dashboard/products?q=478000002')
            ->assertOk()
            ->assertSee('Retsept mahsulot')
            ->assertDontSee('Vitamin C');
    }

    public function test_product_pagination_preserves_query_string(): void
    {
        $admin = User::factory()->create();
        foreach (range(1, 13) as $index) {
            Product::create([
                'name' => "Vitamin {$index}", 'name_uz' => "Vitamin {$index}",
                'name_ru' => "Витамин {$index}", 'name_en' => "Vitamin {$index}",
                'status' => 'active',
            ]);
        }

        $this->actingAs($admin)->get('/dashboard/products?q=Vitamin&status=active')
            ->assertOk()
            ->assertSee('q=Vitamin&amp;status=active&amp;page=2', false);
    }

    public function test_product_validation_keeps_old_input_and_reopens_modal_while_translations_remain_optional(): void
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->from('/dashboard/products')->post(route('dashboard.products.store'), [
            '_form_context' => 'product',
            'name_uz' => 'Saqlanmagan nom',
            'name_ru' => '',
            'name_en' => '',
        ]);

        $response->assertRedirect('/dashboard/products')
            ->assertSessionHasErrors(['status'])
            ->assertSessionDoesntHaveErrors(['name_ru', 'name_en'])
            ->assertSessionHasInput('name_uz', 'Saqlanmagan nom');

        $this->actingAs($admin)->get('/dashboard/products')
            ->assertOk()
            ->assertSee('data-open-modal="createProductModal"', false)
            ->assertSee('value="Saqlanmagan nom"', false);
    }

    public function test_existing_crud_named_routes_remain_registered(): void
    {
        $product = Product::create([
            'name' => 'Route test', 'name_uz' => 'Route test', 'name_ru' => 'Route test', 'name_en' => 'Route test',
            'status' => 'draft',
        ]);

        $this->assertStringContainsString('/dashboard/products', route('dashboard.products.store'));
        $this->assertStringContainsString('/dashboard/products/'.$product->id, route('dashboard.products.update', $product));
        $this->assertStringContainsString('/dashboard/products/'.$product->id, route('dashboard.products.destroy', $product));
        $this->assertStringContainsString('/dashboard/articles', route('dashboard.articles.store'));
        $this->assertStringContainsString('/dashboard/partners', route('dashboard.partners.store'));
        $this->assertStringContainsString('/dashboard/certificates', route('dashboard.certificates.store'));
        $this->assertStringContainsString('/dashboard/contact-messages', route('dashboard.contact-messages.update', 1));
    }
}
