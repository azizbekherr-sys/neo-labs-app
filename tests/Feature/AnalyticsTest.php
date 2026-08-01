<?php

namespace Tests\Feature;

use App\Models\PageView;
use App\Models\User;
use App\Support\AnalyticsClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const CHROME_ANDROID = 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Mobile Safari/537.36';

    public function test_public_page_view_is_classified_and_sensitive_identifiers_are_hashed(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.45'])
            ->withHeaders([
                'User-Agent' => self::CHROME_ANDROID,
                'CF-IPCountry' => 'UZ',
                'CF-IPCity' => 'Tashkent',
            ])->postJson(route('analytics.collect'), [
                'event_id' => '9b633fb8-1122-4a14-922b-c401709228e0',
                'visitor_id' => 'visitor-12345678',
                'session_id' => 'session-12345678',
                'event_type' => 'page_view',
                'path' => '/uz/product/12/pikamin?utm_source=google',
                'title' => 'PIKAMIN — NEO-LABS',
                'landing_path' => '/uz/catalog',
                'referrer' => 'https://www.google.com/search?q=pikamin',
                'screen_width' => 390,
                'screen_height' => 844,
                'client_language' => 'uz-UZ',
                'timezone' => 'Asia/Tashkent',
            ]);

        $response->assertStatus(204);
        $this->assertDatabaseHas('page_views', [
            'event_type' => 'page_view',
            'path' => '/uz/product/12/pikamin',
            'page_type' => 'product',
            'content_id' => 12,
            'locale' => 'uz',
            'channel' => 'organic',
            'device_type' => 'mobile',
            'browser' => 'Chrome',
            'operating_system' => 'Android',
            'country_code' => 'UZ',
            'city' => 'Tashkent',
        ]);

        $view = PageView::firstOrFail();
        $this->assertNotSame('visitor-12345678', $view->visitor_id);
        $this->assertNotSame('session-12345678', $view->session_id);
        $this->assertNotSame('203.0.113.45', $view->ip_hash);
        $this->assertSame(64, strlen($view->visitor_id));
        $this->assertSame(64, strlen($view->ip_hash));
        $this->assertStringNotContainsString('?utm_source', $view->path);
    }

    public function test_bots_authenticated_users_and_duplicate_events_are_not_counted_twice(): void
    {
        $payload = [
            'event_id' => '39a4ba4d-48a7-4cb1-bde5-d2e66ac9be87',
            'visitor_id' => 'visitor-duplicate',
            'session_id' => 'session-duplicate',
            'path' => '/uz/catalog',
        ];

        $this->withHeader('User-Agent', self::CHROME_ANDROID)->postJson(route('analytics.collect'), $payload)->assertStatus(204);
        $this->withHeader('User-Agent', self::CHROME_ANDROID)->postJson(route('analytics.collect'), $payload)->assertStatus(204);

        $botPayload = $payload;
        $botPayload['event_id'] = 'fd6339c5-7965-45e6-b31f-a92727aa4457';
        $this->withHeader('User-Agent', 'Googlebot/2.1')->postJson(route('analytics.collect'), $botPayload)->assertStatus(204);

        $userPayload = $payload;
        $userPayload['event_id'] = '76b32747-0a0d-48d7-bccd-b4b4fc2c3e69';
        $this->actingAs(User::factory()->create())
            ->withHeader('User-Agent', self::CHROME_ANDROID)
            ->postJson(route('analytics.collect'), $userPayload)
            ->assertStatus(204);

        $this->assertDatabaseCount('page_views', 1);
    }

    public function test_channels_and_article_pages_are_classified(): void
    {
        $this->assertSame('ai', AnalyticsClassifier::channel('chatgpt.com', null, null));
        $this->assertSame('social', AnalyticsClassifier::channel('t.me', null, null));
        $this->assertSame('paid', AnalyticsClassifier::channel('google.com', 'google', 'cpc'));
        $this->assertSame('direct', AnalyticsClassifier::channel(null, null, null));

        $page = AnalyticsClassifier::page('/ru/news/41/poleznaya-statya');
        $this->assertSame('article', $page['page_type']);
        $this->assertSame(41, $page['content_id']);
        $this->assertSame('ru', $page['locale']);
    }

    public function test_admin_can_view_report_and_export_csv_while_guests_cannot(): void
    {
        $this->get(route('dashboard.analytics'))->assertRedirect(route('login'));

        $this->createView([
            'path' => '/uz/product/7/pikamin',
            'page_type' => 'product',
            'content_id' => 7,
            'title' => 'PIKAMIN — NEO-LABS',
            'channel' => 'organic',
        ]);
        $this->createView([
            'path' => '/uz/news/4/salomatlik',
            'page_type' => 'article',
            'content_id' => 4,
            'title' => 'Salomatlik maqolasi — NEO-LABS',
            'channel' => 'social',
            'visitor_id' => hash('sha256', 'visitor-two'),
            'session_id' => hash('sha256', 'session-two'),
        ]);

        $admin = User::factory()->create();
        $this->actingAs($admin)->get(route('dashboard.analytics', ['days' => 30]))
            ->assertOk()
            ->assertSee('Sayt analitikasi')
            ->assertSee('PIKAMIN')
            ->assertSee('Salomatlik maqolasi')
            ->assertSee('Top mahsulotlar')
            ->assertSee('Top maqolalar')
            ->assertSee('Oxirgi tashriflar')
            ->assertDontSee('@selected', false);

        $export = $this->actingAs($admin)->get(route('dashboard.analytics.export', ['days' => 30]));
        $export->assertOk()->assertDownload();
        $csv = $export->streamedContent();
        $this->assertStringContainsString('PIKAMIN', $csv);
        $this->assertStringContainsString('/uz/news/4/salomatlik', $csv);
    }

    private function createView(array $attributes = []): PageView
    {
        $path = $attributes['path'] ?? '/uz';

        return PageView::create(array_merge([
            'event_id' => (string) Str::uuid(),
            'visitor_id' => hash('sha256', 'visitor-one'),
            'session_id' => hash('sha256', 'session-one'),
            'event_type' => 'page_view',
            'path' => $path,
            'path_hash' => sha1($path),
            'route_name' => 'home',
            'page_type' => 'home',
            'locale' => 'uz',
            'title' => 'NEO-LABS',
            'channel' => 'direct',
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'operating_system' => 'macOS',
            'occurred_at' => now(),
            'created_at' => now(),
        ], $attributes));
    }
}
