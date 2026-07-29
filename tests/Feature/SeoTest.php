<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Product;
use App\Models\Certificate;
use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_production_metadata_hreflang_and_valid_json_ld(): void
    {
        $response = $this->get('/ru');

        $response->assertOk()
            ->assertSee('<title>NEO-LABS — производство БАД и контрактное производство в Узбекистане</title>', false)
            ->assertSee('<link rel="canonical" href="https://neo-labs.uz/ru"', false)
            ->assertSee('hreflang="ru-UZ"', false)
            ->assertSee('hreflang="uz-UZ"', false)
            ->assertSee('hreflang="en-UZ"', false)
            ->assertDontSee('Laravel');

        $schema = $this->firstJsonLd($response->getContent());
        $this->assertSame('https://schema.org', $schema['@context']);
        $types = collect($schema['@graph'])->pluck('@type')->flatten()->all();
        $this->assertContains('Organization', $types);
        $this->assertContains('WebSite', $types);
        $this->assertContains('WebPage', $types);
        $this->assertContains('BreadcrumbList', $types);
    }

    public function test_language_switcher_links_and_redirects_between_supported_locales(): void
    {
        $response = $this->get('/ru');

        $response->assertOk()
            ->assertSee('href="https://neo-labs.uz/uz" role="menuitem"', false)
            ->assertSee('href="https://neo-labs.uz/en" role="menuitem"', false)
            ->assertDontSee('/locale/uz', false)
            ->assertDontSee('/locale/en', false);

        $this->from('/ru/catalog?q=vitamin')
            ->get('/locale/uz')
            ->assertRedirect('/uz/catalog?q=vitamin');

        $this->from('/uz/news?page=2')
            ->get('/locale/en')
            ->assertRedirect('/en/news?page=2');
    }

    public function test_product_schema_keeps_saved_facts_without_changing_live_detail_layout(): void
    {
        $product = Product::create([
            'name' => 'Test product', 'name_ru' => 'Тестовый продукт', 'name_uz' => 'Test mahsulot',
            'name_en' => 'Test product',
            'description_ru' => 'Описание продукта', 'description_uz' => 'Mahsulot tavsifi',
            'description_en' => 'Product description',
            'composition_ru' => 'Витамин C', 'composition_uz' => 'C vitamini',
            'composition_en' => 'Vitamin C',
            'application_ru' => 'По инструкции', 'application_uz' => 'Yo‘riqnoma bo‘yicha',
            'warnings_ru' => 'Не превышать дозу', 'warnings_uz' => 'Dozani oshirmang',
            'barcode' => '4781234567890', 'sku' => 'NL-TEST', 'manufacturer' => 'NEO-LABS',
            'status' => 'active', 'faqs_ru' => [['question'=>'Вопрос?', 'answer'=>'Ответ.']],
        ]);

        $response = $this->get('/ru/product/' . $product->id . '/testovyi-produkt');
        $response->assertOk()
            ->assertSee('Витамин C')
            ->assertSee('По инструкции')
            ->assertSee('Не превышать дозу')
            ->assertDontSee('id="product-facts-title"', false)
            ->assertDontSee('class="seo-faq"', false);
        $response->assertSee('hreflang="uz-UZ" href="https://neo-labs.uz/uz/product/'.$product->id.'/test-mahsulot"', false);
        $response->assertSee('hreflang="en-UZ" href="https://neo-labs.uz/en/product/'.$product->id.'/test-product"', false);
        $this->assertSame(1, substr_count($response->getContent(), '<link rel="canonical"'));

        $schema = $this->firstJsonLd($response->getContent());
        $productNode = collect($schema['@graph'])->firstWhere('@type', 'Product');
        $this->assertSame('4781234567890', $productNode['gtin13']);
        $this->assertSame('NL-TEST', $productNode['sku']);
        $this->assertArrayNotHasKey('offers', $productNode);

        $this->get('/en/product/' . $product->id . '/test-product')
            ->assertOk()
            ->assertSee('Product description')
            ->assertSee('Vitamin C');
    }

    public function test_same_name_product_variants_get_unique_localized_titles(): void
    {
        $capsules = Product::create([
            'name' => 'FERFOLAS',
            'name_uz' => 'FERFOLAS',
            'name_ru' => 'ФЕРФОЛАС',
            'name_en' => 'FERFOLAS',
            'type_uz' => 'Biologik faol qo‘shimcha, kapsulalar',
            'type_ru' => 'Биологически активная добавка, капсулы',
            'type_en' => 'Dietary supplement, capsules',
            'status' => 'active',
        ]);
        $solution = Product::create([
            'name' => 'FERFOLAS',
            'name_uz' => 'FERFOLAS',
            'name_ru' => 'ФЕРФОЛАС',
            'name_en' => 'FERFOLAS',
            'type_uz' => 'Biologik faol qo‘shimcha, ichish uchun eritma',
            'type_ru' => 'Биологически активная добавка, раствор для приема внутрь',
            'type_en' => 'Dietary supplement, oral solution',
            'status' => 'active',
        ]);

        $capsuleHtml = $this->get("/uz/product/{$capsules->id}/ferfolas")->assertOk()->getContent();
        $solutionHtml = $this->get("/uz/product/{$solution->id}/ferfolas")->assertOk()->getContent();
        preg_match('/<title>(.*?)<\/title>/s', $capsuleHtml, $capsuleTitle);
        preg_match('/<title>(.*?)<\/title>/s', $solutionHtml, $solutionTitle);

        $this->assertNotSame($capsuleTitle[1], $solutionTitle[1]);
        $this->assertStringContainsString('kapsulalar', $capsuleTitle[1]);
        $this->assertStringContainsString('eritma', $solutionTitle[1]);
    }

    public function test_public_home_cache_is_invalidated_when_content_changes(): void
    {
        $this->get('/uz')->assertOk()->assertDontSee('CACHE-REFRESH-PRODUCT');

        Product::create([
            'name' => 'CACHE-REFRESH-PRODUCT',
            'name_uz' => 'CACHE-REFRESH-PRODUCT',
            'name_ru' => 'CACHE-REFRESH-PRODUCT',
            'name_en' => 'CACHE-REFRESH-PRODUCT',
            'status' => 'active',
            'is_featured' => true,
        ]);

        $this->get('/uz')->assertOk()->assertSee('CACHE-REFRESH-PRODUCT');
    }

    public function test_public_pages_keep_the_live_site_visual_structure(): void
    {
        $this->get('/ru')
            ->assertOk()
            ->assertSee('<span>С заботой</span>', false)
            ->assertSee('<span class="accent">О здоровье</span>', false)
            ->assertSee('<span>Каждого</span>', false)
            ->assertSee('src="img/untitled.png"', false)
            ->assertSee('Чем помогают наши БАД-средства?')
            ->assertSee('class="seo-faq"', false)
            ->assertSee('"@type":"FAQPage"', false);

        $this->get('/ru/about')
            ->assertOk()
            ->assertSee("background:url('https://neo-labs.uz/img/aboutbg.png')", false)
            ->assertSee('src="https://neo-labs.uz/img/about1.png"', false)
            ->assertSee('src="https://neo-labs.uz/img/about2.png"', false)
            ->assertSee('src="https://neo-labs.uz/img/about3.png"', false)
            ->assertSee('src="https://neo-labs.uz/img/about4.png"', false)
            ->assertDontSee('Подтверждённые сведения и прозрачность');
    }

    public function test_product_cards_are_reusable_equal_height_responsive_and_accessible(): void
    {
        foreach (range(1, 4) as $index) {
            Product::create([
                'name' => 'Product '.$index,
                'name_ru' => $index === 2 ? 'Очень длинное название продукта для проверки переноса строк' : 'Продукт '.$index,
                'name_uz' => 'Mahsulot '.$index,
                'name_en' => 'Product '.$index,
                'type_ru' => $index === 3 ? 'Длинная категория и описание пользы продукта для проверки ограничения' : 'Поддержка здоровья',
                'type_uz' => 'Salomatlik uchun',
                'type_en' => 'Health support',
                'description_ru' => $index === 4 ? null : '<p>Длинное <strong>описание продукта</strong>, которое должно быть очищено и ограничено тремя строками без нарушения карточки.</p>',
                'description_uz' => 'Mahsulot tavsifi',
                'description_en' => 'Product description',
                'image' => $index === 4 ? null : 'products/p_691652d6885f1_1763070678.png',
                'status' => 'active',
                'is_featured' => true,
            ]);
        }

        $response = $this->get('/ru/catalog');
        $response->assertOk()
            ->assertSee('loading="lazy"', false)
            ->assertSee('class="nl-product-card__media is-fallback"', false)
            ->assertSee('aria-label="Открыть продукт Продукт 1"', false)
            ->assertDontSee('<strong>описание продукта</strong>', false);

        $siteCss = file_get_contents(public_path('css/site.css'));
        $this->assertStringContainsString('grid-template-columns: repeat(4, minmax(0, 1fr))', $siteCss);
        $this->assertStringContainsString('@media (min-width: 768px) and (max-width: 1279px)', $siteCss);
        $this->assertStringContainsString('@media (max-width: 767px)', $siteCss);
        $this->assertStringContainsString('-webkit-line-clamp: 3', $siteCss);
        $this->assertStringContainsString('margin-top: auto', $siteCss);

        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/class="nl-product-card__image"[\s\S]+?width="\d+"[\s\S]+?height="\d+"/', $html);
        $this->assertSame(4, substr_count($html, 'class="nl-product-card"'));
        $this->assertSame(4, substr_count($html, 'class="nl-product-card__title"'));
        $this->assertSame(4, substr_count($html, 'class="nl-product-card__button"'));
        $this->assertSame(4, substr_count($html, 'class="nl-product-card__link"'));

        $home = $this->get('/ru');
        $home->assertOk();
        $this->assertSame(4, substr_count($home->getContent(), 'class="nl-product-card"'));
    }

    public function test_manufacturing_landing_is_localized_responsive_and_uses_verified_content(): void
    {
        Partner::create([
            'path' => 'partners/pr_69280b6e953d2_1764232046.PNG',
            'url' => 'https://uzum.uz/uz/shop/life-prime',
        ]);
        Certificate::create([
            'name_uz' => 'Tasdiqlangan hujjat',
            'name_ru' => 'Подтверждённый документ',
            'name_en' => 'Verified document',
            'number' => 'DOC-1',
            'is_published' => true,
        ]);

        $expectations = [
            'uz' => ['Kontrakt ishlab chiqarish', 'Loyihangizni muhokama qilaylik'],
            'ru' => ['Контрактное производство', 'Давайте обсудим ваш проект'],
            'en' => ['Contract manufacturing', 'Let’s discuss your project'],
        ];

        foreach ($expectations as $locale => [$heading, $cta]) {
            $response = $this->get("/{$locale}/manufacturing");
            $response->assertOk()
                ->assertSee('<h1 id="mfg-title">'.$heading.'</h1>', false)
                ->assertSee($cta)
                ->assertSee('href="tel:+998991018839"', false)
                ->assertSee('href="mailto:neo_labs2019@mail.ru"', false)
                ->assertSee('aria-current="page"', false)
                ->assertSee('class="skip-link"', false)
                ->assertSee('name="form_context" value="manufacturing"', false)
                ->assertSee('loading="lazy"', false)
                ->assertSee('decoding="async"', false)
                ->assertSee('LifePrime', false)
                ->assertSee('DOC-1')
                ->assertDontSee('id="pageLoader"', false);

            $html = $response->getContent();
            $this->assertSame(4, substr_count($html, 'class="mfg-process-card"'));
            $this->assertSame(4, substr_count($html, 'class="mfg-capacity-card"'));
            $this->assertStringContainsString('type="image/avif"', $html);
            $this->assertStringContainsString('fetchpriority="high"', $html);
        }

        $css = file_get_contents(public_path('css/manufacturing.css'));
        $this->assertStringContainsString('@media (max-width: 1100px)', $css);
        $this->assertStringContainsString('@media (max-width: 820px)', $css);
        $this->assertStringContainsString('@media (max-width: 600px)', $css);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertFileExists(public_path('img/responsive/neo-labs-contract-manufacturing-640.avif'));
        $this->assertFileExists(public_path('partners/optimized/pr_69280b6e953d2_1764232046.webp'));
    }

    public function test_manufacturing_enquiry_reuses_contact_endpoint_with_validation_and_spam_protection(): void
    {
        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.chat_id' => '12345',
        ]);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $payload = [
            'form_context' => 'manufacturing',
            'name' => 'Aziz',
            'company' => 'Test Company',
            'contact' => '+998 90 123 45 67',
            'product_type' => 'Tabletka',
            'message' => 'Blister qadoqlash bo‘yicha hisob-kitob kerak.',
            'website' => '',
        ];

        $this->from('/uz/manufacturing')
            ->post('/contact/send', $payload)
            ->assertRedirect('/uz/manufacturing')
            ->assertSessionHas('contact_ok');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org')
                && str_contains((string) $request['text'], 'Test Company')
                && str_contains((string) $request['text'], 'Tabletka');
        });

        $this->from('/uz/manufacturing')
            ->post('/contact/send', array_merge($payload, ['contact' => 'not-a-contact']))
            ->assertSessionHasErrors('contact');

        $this->from('/uz/manufacturing')
            ->post('/contact/send', array_merge($payload, ['website' => 'spam.example']))
            ->assertSessionHasErrors('website');

        $this->from('/ru/contacts')
            ->post('/contact/send', [
                'name' => 'Existing form',
                'phone' => '+998 99 101 88 39',
                'message' => 'Existing contact form compatibility check.',
            ])
            ->assertRedirect('/ru/contacts')
            ->assertSessionHas('contact_ok');
    }

    public function test_article_exposes_real_author_reviewer_and_references(): void
    {
        $article = Article::create([
            'title_ru' => 'Проверенная статья', 'title_uz' => 'Tekshirilgan maqola',
            'title_en' => 'Verified article',
            'description_ru' => '<p>Содержание</p>', 'description_uz' => '<p>Mazmun</p>',
            'description_en' => '<p>Article content</p>',
            'author_name' => 'Иван Иванов', 'author_slug' => 'ivan-ivanov', 'author_role_ru' => 'Редактор',
            'reviewer_name' => 'Анна Врач', 'reviewer_role_ru' => 'Врач', 'reviewed_at' => now(),
            'references_ru' => ['https://www.who.int/'], 'schema_type' => 'BlogPosting',
            'references_en' => ['https://www.who.int/'],
            'photo' => 'https://media.example.test/articles/verified.jpg',
        ]);

        $response = $this->get('/ru/news/' . $article->id . '/proverennaya-statya');
        $response->assertOk()
            ->assertSee('Иван Иванов')
            ->assertSee('Анна Врач')
            ->assertSee('https://www.who.int/')
            ->assertSee('property="og:image" content="https://media.example.test/articles/verified.jpg"', false)
            ->assertSee('rel="preload" as="image" href="https://media.example.test/articles/verified.jpg"', false)
            ->assertDontSee('https://neo-labs.uz/articles/verified.jpg', false);
        $schema = $this->firstJsonLd($response->getContent());
        $node = collect($schema['@graph'])->firstWhere('@type', 'BlogPosting');
        $this->assertSame('Иван Иванов', $node['author']['name']);
        $this->assertSame('Анна Врач', $node['reviewedBy']['name']);

        $this->get('/en/news/' . $article->id . '/verified-article')
            ->assertOk()
            ->assertSee('Verified article')
            ->assertSee('Article content');
    }

    public function test_sitemap_never_contains_localhost_and_has_language_and_image_data(): void
    {
        Product::create([
            'name' => 'Test', 'name_ru' => 'Тест', 'name_uz' => 'Test',
            'status' => 'active', 'images' => ['products/test.webp'],
        ]);
        $index = $this->get('/sitemap.xml');
        $index->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<sitemapindex', false)
            ->assertSee('https://neo-labs.uz/sitemaps/pages-uz.xml', false)
            ->assertSee('https://neo-labs.uz/sitemaps/products.xml', false)
            ->assertSee('https://neo-labs.uz/sitemaps/articles.xml', false)
            ->assertDontSee('localhost');
        $this->assertNotFalse(simplexml_load_string($index->getContent()));

        $response = $this->get('/sitemaps/products.xml');
        $response->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('https://neo-labs.uz/ru/product/', false)
            ->assertSee('hreflang="uz-UZ"', false)
            ->assertSee('hreflang="x-default" href="https://neo-labs.uz/uz/product/', false)
            ->assertSee('<image:image>', false)
            ->assertDontSee('localhost');
        $this->assertNotFalse(simplexml_load_string($response->getContent()));
    }

    public function test_legacy_root_unknown_urls_manifest_privacy_and_global_breadcrumbs(): void
    {
        $this->get('/')->assertStatus(301)->assertRedirect('/uz');
        $this->get('/catalog')->assertStatus(301)->assertRedirect('/uz/catalog');

        $this->get('/not-found-seo-audit')
            ->assertNotFound()
            ->assertSee('content="noindex, nofollow"', false);
        $this->get('/ru/not-found-seo-audit')
            ->assertNotFound()
            ->assertSee('<html lang="ru">', false);

        foreach (['uz', 'ru', 'en'] as $locale) {
            $this->get("/{$locale}/privacy")
                ->assertOk()
                ->assertSee('<article class="container policy-page">', false)
                ->assertSee('<link rel="canonical"', false);
        }

        $this->get('/uz/catalog')
            ->assertOk()
            ->assertSee('class="site-breadcrumb"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('<link rel="manifest" href="https://neo-labs.uz/site.webmanifest"', false);

        $this->assertFileExists(public_path('site.webmanifest'));
        $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true);
        $this->assertSame('/uz', $manifest['start_url']);
        $this->assertFileExists(public_path('img/icon-192.png'));
        $this->assertFileExists(public_path('img/icon-512.png'));
    }

    public function test_organization_schema_has_opening_hours_and_uzbek_x_default(): void
    {
        $response = $this->get('/en');
        $response->assertOk()
            ->assertSee('hreflang="x-default" href="https://neo-labs.uz/uz"', false);

        $schema = $this->firstJsonLd($response->getContent());
        $organization = collect($schema['@graph'])->first(function (array $node) {
            return in_array('Organization', (array) ($node['@type'] ?? []), true);
        });
        $hours = $organization['openingHoursSpecification'][0];
        $this->assertSame('OpeningHoursSpecification', $hours['@type']);
        $this->assertSame('09:00', $hours['opens']);
        $this->assertSame('18:00', $hours['closes']);
    }

    public function test_filtered_results_are_noindex_and_canonical_has_no_search_query(): void
    {
        $response = $this->get('/uz/catalog?q=test');
        $response->assertOk()->assertSee('content="noindex, follow, max-image-preview:large"', false);
        $this->assertStringContainsString('href="https://neo-labs.uz/uz/catalog"', $response->getContent());
        $this->assertStringNotContainsString('canonical" href="https://neo-labs.uz/uz/catalog?q=', $response->getContent());
    }

    public function test_all_company_routes_render_in_all_languages_with_one_canonical(): void
    {
        foreach (['about','certificates','production','editorial-policy','company-facts','manufacturing','contacts','privacy'] as $path) {
            foreach (['ru','uz','en'] as $locale) {
                $response = $this->get("/{$locale}/{$path}");
                $response->assertOk()->assertDontSee('APP_NAME=Laravel');
                $this->assertSame(1, substr_count($response->getContent(), '<link rel="canonical"'), "Duplicate canonical on {$locale}/{$path}");
                $this->assertAllJsonLdIsValid($response->getContent());
            }
        }
    }

    public function test_published_certificate_has_detail_url_and_schema(): void
    {
        $certificate = Certificate::create([
            'name_ru'=>'Тестовый сертификат', 'name_uz'=>'Test sertifikati', 'number'=>'CERT-1',
            'name_en'=>'Test certificate',
            'issuer_ru'=>'Организация', 'issuer_uz'=>'Tashkilot', 'is_published'=>true,
            'issuer_en'=>'Organization',
        ]);
        $response = $this->get('/ru/certificates/'.$certificate->id.'/testovyi-sertifikat');
        $response->assertOk()->assertSee('CERT-1');
        $schema = $this->firstJsonLd($response->getContent());
        $this->assertNotNull(collect($schema['@graph'])->firstWhere('@type','CreativeWork'));

        $this->get('/en/certificates/'.$certificate->id.'/test-certificate')
            ->assertOk()
            ->assertSee('Organization');
        $this->get('/sitemaps/pages-en.xml')
            ->assertOk()
            ->assertSee('https://neo-labs.uz/en/certificates/'.$certificate->id.'/test-certificate', false)
            ->assertSee('hreflang="uz-UZ" href="https://neo-labs.uz/uz/certificates/'.$certificate->id.'/test-sertifikati"', false);
    }

    public function test_private_routes_send_noindex_header(): void
    {
        $this->get('/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $robots = file_get_contents(public_path('robots.txt'));
        $this->assertStringContainsString('Sitemap: https://neo-labs.uz/sitemap.xml', $robots);
        $this->assertStringContainsString('Disallow: /dashboard', $robots);
        $this->assertFileExists(public_path('llms.txt'));
        $this->assertFileExists(public_path('llms-full.txt'));
    }

    public function test_footer_is_semantic_localized_and_responsive(): void
    {
        $expectations = [
            'uz' => [
                'navigation' => 'Footer navigatsiyasi',
                'description' => 'NEO-LABS — Toshkentda biologik faol qo‘shimchalar ishlab chiqaruvchi',
                'address' => 'O‘zbekiston, Toshkent shahri, Sergeli tumani',
                'map' => 'Xaritada ko‘rish',
            ],
            'ru' => [
                'navigation' => 'Навигация в подвале сайта',
                'description' => 'NEO-LABS — компания из Ташкента',
                'address' => 'Узбекистан, город Ташкент, Сергелийский район',
                'map' => 'Посмотреть на карте',
            ],
            'en' => [
                'navigation' => 'Footer navigation',
                'description' => 'NEO-LABS is a Tashkent-based manufacturer',
                'address' => 'Sergeli district, Tashkent, Uzbekistan',
                'map' => 'View on map',
            ],
        ];

        foreach ($expectations as $locale => $copy) {
            $response = $this->get("/{$locale}");
            $response->assertOk()
                ->assertSee('<footer class="site-footer">', false)
                ->assertSee('<address class="site-footer__address">', false)
                ->assertSee('aria-label="'.$copy['navigation'].'"', false)
                ->assertSee($copy['description'])
                ->assertSee($copy['address'])
                ->assertSee($copy['map'])
                ->assertSee('href="tel:+998991018839"', false)
                ->assertSee('href="mailto:neo_labs2019@mail.ru"', false)
                ->assertSee("/{$locale}/contacts#contacts-map", false)
                ->assertSee('© '.now()->year)
                ->assertDontSee('class="footer-grid"', false);
        }

        $css = file_get_contents(public_path('css/site.css'));
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1.25fr)', $css);
        $this->assertStringContainsString('@media (min-width: 768px) and (max-width: 1023px)', $css);
        $this->assertStringContainsString('@media (max-width: 767px)', $css);
        $this->assertStringContainsString('min-height: 44px', $css);
    }

    public function test_contact_page_defers_third_party_widgets_and_keeps_product_context(): void
    {
        $response = $this->get('/uz/contacts?product=PIKAMIN');

        $response->assertOk()
            ->assertSee('name="product_type" value="PIKAMIN"', false)
            ->assertSee('data-lazy-map', false)
            ->assertSee('data-src="https://www.google.com/maps/embed?', false)
            ->assertSee('requestIdleCallback', false)
            ->assertSee('}, 6000);', false)
            ->assertSee('hreflang="uz-UZ" href="https://neo-labs.uz/uz/contacts"', false)
            ->assertDontSee('/uz/contacts?view=', false);

        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/<iframe[^>]+data-lazy-map[^>]+data-src="[^"]+"(?![^>]+src=)[^>]*>/', $html);

        $nginx = file_get_contents(base_path('docker/nginx.conf'));
        $this->assertStringContainsString('gzip on;', $nginx);
        $this->assertStringContainsString('max-age=31536000, immutable', $nginx);

        $php = file_get_contents(base_path('docker/php.ini'));
        $this->assertStringContainsString('opcache.enable = 1', $php);
    }

    /** @return array<string,mixed> */
    private function firstJsonLd(string $html): array
    {
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $match);
        $this->assertNotEmpty($match[1] ?? null);
        $decoded = json_decode($match[1], true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), json_last_error_msg());
        return $decoded;
    }

    private function assertAllJsonLdIsValid(string $html): void
    {
        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);
        $this->assertNotEmpty($matches[1]);
        foreach ($matches[1] as $json) {
            json_decode($json, true);
            $this->assertSame(JSON_ERROR_NONE, json_last_error(), json_last_error_msg());
        }
    }
}
