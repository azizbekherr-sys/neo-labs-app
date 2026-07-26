<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackendManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_first_admin_can_use_public_registration(): void
    {
        $this->post('/register', [
            'name' => 'Administrator',
            'email' => 'ADMIN@EXAMPLE.COM',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);

        $this->post('/logout');
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Second admin',
            'email' => 'second@example.com',
            'password' => 'secure-password-456',
            'password_confirmation' => 'secure-password-456',
        ])->assertNotFound();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_contact_request_is_saved_and_delivery_is_tracked(): void
    {
        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.chat_id' => '123456',
        ]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $this->from('/uz/contacts')->post('/contact/send', [
            'form_context' => 'general',
            'name' => 'Ali Valiyev',
            'phone' => '+998 90 123 45 67',
            'message' => 'Mahsulot haqida batafsil ma’lumot kerak.',
            'website' => '',
        ])->assertRedirect('/uz/contacts')->assertSessionHas('contact_ok');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Ali Valiyev',
            'status' => 'new',
            'delivery_status' => 'sent',
            'context' => 'general',
        ]);
        Http::assertSentCount(1);
    }

    public function test_admin_can_manage_contact_status_and_draft_products_are_private(): void
    {
        $admin = User::factory()->create();
        $message = ContactMessage::create([
            'name' => 'Mijoz',
            'phone' => '+998901234567',
            'message' => 'Sinov murojaati',
        ]);

        $this->patch(route('dashboard.contact-messages.update', $message), ['status' => 'read'])
            ->assertRedirect('/login');

        $this->actingAs($admin)
            ->patch(route('dashboard.contact-messages.update', $message), ['status' => 'read'])
            ->assertRedirect();
        $this->assertNotNull($message->fresh()->read_at);

        $this->actingAs($admin)->post(route('dashboard.products.store'), [
            'name_uz' => 'Sinov mahsuloti',
            'name_ru' => 'Тестовый продукт',
            'name_en' => 'Test product',
            'status' => 'draft',
            'content_status' => 'incomplete',
            'is_featured' => '1',
        ])->assertRedirect(route('dashboard.products.index'));

        $product = Product::where('name_uz', 'Sinov mahsuloti')->firstOrFail();
        $this->assertSame('draft', $product->status);
        $this->assertTrue($product->is_featured);
        $this->get('/uz/product/' . $product->id . '/sinov-mahsuloti')->assertNotFound();
    }

    public function test_certificate_replacement_and_deletion_clean_up_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('dashboard.certificates.store'), [
            'name_uz' => 'ISO sertifikat',
            'name_ru' => 'Сертификат ISO',
            'name_en' => 'ISO certificate',
            'document' => UploadedFile::fake()->create('old.pdf', 100, 'application/pdf'),
            'is_published' => '1',
        ])->assertRedirect();

        $certificate = Certificate::firstOrFail();
        $oldPath = $certificate->document_path;
        Storage::disk('public')->assertExists($oldPath);

        $this->actingAs($admin)->patch(route('dashboard.certificates.update', $certificate), [
            'name_uz' => 'ISO sertifikat 2',
            'name_ru' => 'Сертификат ISO 2',
            'name_en' => 'ISO certificate 2',
            'document' => UploadedFile::fake()->create('new.pdf', 120, 'application/pdf'),
            'is_published' => '1',
        ])->assertRedirect();

        $newPath = $certificate->fresh()->document_path;
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);

        $this->actingAs($admin)->delete(route('dashboard.certificates.destroy', $certificate))->assertRedirect();
        Storage::disk('public')->assertMissing($newPath);
        $this->assertDatabaseCount('certificates', 0);
    }
}
