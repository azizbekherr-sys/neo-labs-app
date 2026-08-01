<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Models\Product;
use App\Models\Partner;
use App\Models\Article;
use App\Models\Certificate;
use App\Models\CompanyFact;
use App\Http\Controllers\SitemapController;
use App\Support\PublicContentCache;

/**
 * Helpers
 */
$supportedLocales = ['ru', 'uz', 'en'];
$defaultLocale = in_array(config('seo.default_locale'), $supportedLocales, true)
    ? (string) config('seo.default_locale')
    : 'uz';
$pickLocalized = function ($model, string $base, string $locale) {
    $fieldByLocale = $base . '_' . $locale;
    return $model->{$fieldByLocale}
        ?? $model->{$base . '_ru'}
        ?? $model->{$base . '_uz'}
        ?? $model->{$base . '_en'}
        ?? $model->{$base}
        ?? null;
};

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/sitemaps/pages-{locale}.xml', [SitemapController::class, 'pages'])
    ->where(['locale' => implode('|', $supportedLocales)])
    ->name('sitemap.pages');
Route::get('/sitemaps/products.xml', [SitemapController::class, 'products'])->name('sitemap.products');
Route::get('/sitemaps/articles.xml', [SitemapController::class, 'articles'])->name('sitemap.articles');

/**
 * Legacy URLs -> 301 to locale-prefixed URLs
 */
Route::get('/', function () use ($defaultLocale) {
    return redirect()->to('/' . $defaultLocale, 301);
});
Route::get('/catalog', function () use ($defaultLocale) {
    return redirect()->to('/' . $defaultLocale . '/catalog', 301);
});
Route::get('/manufacturing', function () use ($defaultLocale) {
    return redirect()->to('/' . $defaultLocale . '/manufacturing', 301);
});
Route::get('/about', function () use ($defaultLocale) {
    return redirect()->to('/' . $defaultLocale . '/about', 301);
});
Route::get('/contacts', function () use ($defaultLocale) {
    return redirect()->to('/' . $defaultLocale . '/contacts', 301);
});
foreach (['certificates', 'production', 'editorial-policy', 'company-facts', 'privacy'] as $legacyPage) {
    Route::get('/' . $legacyPage, function () use ($defaultLocale, $legacyPage) {
        return redirect()->to('/' . $defaultLocale . '/' . $legacyPage, 301);
    });
}
Route::get('/news', function (Request $request) use ($defaultLocale) {
    $qs = $request->getQueryString();
    $to = '/' . $defaultLocale . '/news' . ($qs ? ('?' . $qs) : '');
    return redirect()->to($to, 301);
});
Route::get('/news/{article}/{slug?}', function (Article $article) use ($defaultLocale, $pickLocalized) {
    $locale = $defaultLocale;
    $title = (string) ($pickLocalized($article, 'title', $locale) ?? ('news-' . $article->id));
    $slug = Str::slug($title);
    $to = '/' . $locale . '/news/' . $article->id . '/' . $slug;
    return redirect()->to($to, 301);
});
Route::get('/product/{product}/{slug?}', function (Product $product) use ($defaultLocale, $pickLocalized) {
    $locale = $defaultLocale;
    $name = (string) ($pickLocalized($product, 'name', $locale) ?? $product->name ?? ('product-' . $product->id));
    $slug = Str::slug($name);
    $to = '/' . $locale . '/product/' . $product->id . '/' . $slug;
    return redirect()->to($to, 301);
});

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ArticlesController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CompanyFactController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\AdminDashboardController;

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1')->name('register.post');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/admin', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('admin.entry');

// Contact form -> Telegram
Route::post('/contact/send', [ContactController::class, 'send'])
    ->middleware('throttle:6,1')
    ->name('contact.send');

// Protected area
Route::middleware(['auth', \App\Http\Middleware\AdminLocale::class])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/products', [AdminDashboardController::class, 'products'])->name('dashboard.products.index');
    Route::get('/dashboard/articles', [AdminDashboardController::class, 'articles'])->name('dashboard.articles.index');
    Route::get('/dashboard/partners', [AdminDashboardController::class, 'partners'])->name('dashboard.partners.index');
    Route::get('/dashboard/certificates', [AdminDashboardController::class, 'certificates'])->name('dashboard.certificates.index');
    Route::get('/dashboard/company-facts', [AdminDashboardController::class, 'companyFacts'])->name('dashboard.company-facts.index');
    // Backward-compatible alias for the old combined "Documents" section
    Route::get('/dashboard/documents', fn () => redirect()->route('dashboard.certificates.index'))->name('dashboard.documents.index');
    Route::get('/dashboard/messages', [AdminDashboardController::class, 'messages'])->name('dashboard.messages.index');
    Route::resource('products', ProductController::class)->except(['show']);
    // Dashboard-prefixed fallbacks (server /products yo'lini qo'llamagan holatlar uchun)
    Route::get('/dashboard/products/create', [ProductController::class, 'create'])->name('dashboard.products.create');
    Route::post('/dashboard/products/ai-fill', [\App\Http\Controllers\AiContentController::class, 'fillProduct'])->middleware('throttle:20,1')->name('dashboard.products.ai-fill');
    Route::post('/dashboard/products', [ProductController::class, 'store'])->name('dashboard.products.store');
    Route::get('/dashboard/products/{product}/edit', [ProductController::class, 'edit'])->name('dashboard.products.edit');
    Route::put('/dashboard/products/{product}', [ProductController::class, 'update'])->name('dashboard.products.update');
    Route::delete('/dashboard/products/{product}', [ProductController::class, 'destroy'])->name('dashboard.products.destroy');

    // Partners logos management
    Route::post('/dashboard/partners', [PartnerController::class, 'store'])->name('dashboard.partners.store');
    Route::patch('/dashboard/partners/{partner}', [PartnerController::class, 'update'])->name('dashboard.partners.update');
    Route::delete('/dashboard/partners/{partner}', [PartnerController::class, 'destroy'])->name('dashboard.partners.destroy');

    // Articles management
    Route::get('/dashboard/articles/create', [\App\Http\Controllers\ArticleController::class, 'create'])->name('dashboard.articles.create');
    Route::post('/dashboard/articles/ai-fill', [\App\Http\Controllers\AiContentController::class, 'fillArticle'])->middleware('throttle:20,1')->name('dashboard.articles.ai-fill');
    // Stepwise AI toolkit — each step is an independent request
    Route::post('/dashboard/articles/ai/import', [\App\Http\Controllers\AiContentController::class, 'importArticle'])->middleware('throttle:20,1')->name('dashboard.articles.ai.import');
    Route::post('/dashboard/articles/ai/translate', [\App\Http\Controllers\AiContentController::class, 'translateArticle'])->middleware('throttle:20,1')->name('dashboard.articles.ai.translate');
    Route::post('/dashboard/articles/ai/seo', [\App\Http\Controllers\AiContentController::class, 'generateSeo'])->middleware('throttle:30,1')->name('dashboard.articles.ai.seo');
    Route::post('/dashboard/articles/ai/image', [\App\Http\Controllers\AiContentController::class, 'articleImage'])->middleware('throttle:40,1')->name('dashboard.articles.ai.image');
    Route::post('/dashboard/articles', [\App\Http\Controllers\ArticleController::class, 'store'])->name('dashboard.articles.store');
    Route::get('/dashboard/articles/{article}/edit', [\App\Http\Controllers\ArticleController::class, 'edit'])->name('dashboard.articles.edit');
    Route::put('/dashboard/articles/{article}', [\App\Http\Controllers\ArticleController::class, 'update'])->name('dashboard.articles.update');
    Route::delete('/dashboard/articles/{article}', [\App\Http\Controllers\ArticleController::class, 'destroy'])->name('dashboard.articles.destroy');
    Route::post('/dashboard/certificates', [CertificateController::class, 'store'])->name('dashboard.certificates.store');
    Route::patch('/dashboard/certificates/{certificate}', [CertificateController::class, 'update'])->name('dashboard.certificates.update');
    Route::delete('/dashboard/certificates/{certificate}', [CertificateController::class, 'destroy'])->name('dashboard.certificates.destroy');
    Route::post('/dashboard/company-facts', [CompanyFactController::class, 'store'])->name('dashboard.company-facts.store');
    Route::patch('/dashboard/company-facts/{companyFact}', [CompanyFactController::class, 'update'])->name('dashboard.company-facts.update');
    Route::delete('/dashboard/company-facts/{companyFact}', [CompanyFactController::class, 'destroy'])->name('dashboard.company-facts.destroy');
    Route::patch('/dashboard/contact-messages/{contactMessage}', [ContactMessageController::class, 'update'])->name('dashboard.contact-messages.update');
    Route::delete('/dashboard/contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('dashboard.contact-messages.destroy');
});

// Locale switcher
Route::get('/locale/{locale}', function (Request $request, string $locale) use ($supportedLocales) {
    if (!in_array($locale, $supportedLocales, true)) {
        $locale = 'ru';
    }

    session(['locale' => $locale]);
    app()->setLocale($locale);

    // Redirect to the same page but with new locale prefix
    $back = url()->previous();
    $path = parse_url($back, PHP_URL_PATH) ?: '/';
    $qs = parse_url($back, PHP_URL_QUERY);
    $segments = explode('/', ltrim($path, '/'));

    if (!empty($segments[0]) && in_array($segments[0], $supportedLocales, true)) {
        $segments[0] = $locale;
    } else {
        array_unshift($segments, $locale);
    }

    $newPath = '/' . implode('/', array_values(array_filter($segments, fn ($s) => $s !== '')));
    $to = $newPath . ($qs ? ('?' . $qs) : '');
    return redirect()->to($to);
})->name('locale.switch');

/**
 * Locale-prefixed public pages (SEO-friendly)
 */
Route::prefix('{locale}')
    ->where(['locale' => implode('|', $supportedLocales)])
    ->group(function () use ($pickLocalized) {
        // Home
        Route::get('/', function () {
            $products = Cache::remember('public.home.products', PublicContentCache::TTL, fn () => Product::query()
                ->where('status', 'active')
                ->where('is_featured', true)
                ->latest('id')
                ->take(4)
                ->get());

            $partners = Cache::remember('public.home.partners', PublicContentCache::TTL, fn () => Partner::query()->orderBy('id')->get());
            $articles = Cache::remember('public.home.articles', PublicContentCache::TTL, fn () => Article::query()->latest('id')->take(3)->get());

            return view('welcome', compact('products', 'partners', 'articles'));
        })->name('home');

        // Catalog (supports ?q= search for users + internal linking)
        Route::get('/catalog', function (Request $request) {
            $productsQuery = Product::query()
                ->where('status', 'active')
                ->latest('id');

            if ($request->filled('q')) {
                $q = trim((string) $request->input('q'));
                $like = "%{$q}%";
                $productsQuery->where(function ($sub) use ($like) {
                    $sub->where('name_ru', 'like', $like)
                        ->orWhere('name_uz', 'like', $like)
                        ->orWhere('name_en', 'like', $like)
                        ->orWhere('name', 'like', $like)
                        ->orWhere('type_ru', 'like', $like)
                        ->orWhere('type_uz', 'like', $like)
                        ->orWhere('type_en', 'like', $like);
                });
            }

            $products = $productsQuery->paginate(12)->withQueryString();
            return view('pages.products', compact('products'));
        })->name('catalog');

        Route::get('/manufacturing', function () {
            $partners = Cache::remember('public.manufacturing.partners', PublicContentCache::TTL, fn () => Partner::query()->orderBy('id')->get());
            $certificates = Cache::remember('public.manufacturing.certificates', PublicContentCache::TTL, fn () => Certificate::query()
                ->where('is_published', true)
                ->latest('issued_at')
                ->get());
            return view('pages.manufacturing', compact('partners', 'certificates'));
        })->name('manufacturing');

        Route::view('/about', 'pages.about')->name('about');
        Route::view('/contacts', 'pages.contacts')->name('contacts');
        Route::get('/certificates', function () {
            $certificates = Cache::remember('public.certificates', PublicContentCache::TTL, fn () => Certificate::query()
                ->where('is_published', true)
                ->latest('issued_at')
                ->get());
            return view('pages.certificates', compact('certificates'));
        })->name('certificates');
        Route::get('/certificates/{certificate}/{slug?}', function (string $locale, Certificate $certificate, ?string $slug = null) {
            abort_unless($certificate->is_published, 404);
            $name = $certificate->{'name_' . $locale} ?: $certificate->name_ru;
            $expected = Str::slug($name ?: 'certificate-' . $certificate->id);
            if ($slug !== $expected) {
                return redirect()->route('certificates.show', compact('locale', 'certificate') + ['slug' => $expected], 301);
            }
            return view('pages.certificate-show', compact('certificate'));
        })->name('certificates.show');
        Route::view('/production', 'pages.production')->name('production');
        Route::view('/editorial-policy', 'pages.editorial-policy')->name('editorial-policy');
        Route::view('/privacy', 'pages.privacy')->name('privacy');
        Route::get('/company-facts', function () {
            $facts = Cache::remember('public.company-facts', PublicContentCache::TTL, fn () => CompanyFact::query()
                ->where('is_published', true)
                ->orderBy('key')
                ->get());
            return view('pages.company-facts', compact('facts'));
        })->name('company-facts');
        Route::get('/authors/{slug}', function (string $locale, string $slug) {
            $articles = Article::query()->where('author_slug', $slug)->latest()->get();
            abort_if($articles->isEmpty(), 404);
            $author = $articles->first();
            return view('pages.author', compact('articles', 'author'));
        })->name('authors.show');

        // Products: /{locale}/product/{id}/{slug}
        Route::get('/product/{product}/{slug?}', function (string $locale, Product $product, ?string $slug = null) use ($pickLocalized) {
            abort_unless($product->status === 'active', 404);
            $name = (string) ($pickLocalized($product, 'name', $locale) ?? $product->name ?? ('product-' . $product->id));
            $expected = Str::slug($name);
            if ($slug !== $expected) {
                return redirect()->route('product.show', ['product' => $product, 'slug' => $expected, 'locale' => $locale], 301);
            }
            $relatedProducts = $product->relatedProducts()
                ->where('products.status', 'active')
                ->where('products.id', '!=', $product->getKey())
                ->get()
                ->unique('id')
                ->take(4)
                ->values();

            if ($relatedProducts->count() < 4 && $product->category_id) {
                $categoryProducts = Product::query()
                    ->where('status', 'active')
                    ->where('category_id', $product->category_id)
                    ->where('id', '!=', $product->getKey())
                    ->whereNotIn('id', $relatedProducts->pluck('id'))
                    ->latest('id')
                    ->take(4 - $relatedProducts->count())
                    ->get();
                $relatedProducts = $relatedProducts->concat($categoryProducts);
            }

            if ($relatedProducts->count() < 4) {
                $fallbackProducts = Product::query()
                    ->where('status', 'active')
                    ->where('id', '!=', $product->getKey())
                    ->whereNotIn('id', $relatedProducts->pluck('id'))
                    ->latest('id')
                    ->take(4 - $relatedProducts->count())
                    ->get();
                $relatedProducts = $relatedProducts->concat($fallbackProducts);
            }

            $relatedProducts = $relatedProducts->unique('id')->take(4)->values();
            return view('pages.product-show', compact('product', 'relatedProducts'));
        })->name('product.show');

        // Articles
        Route::get('/news', [ArticlesController::class, 'index'])->name('articles');

        Route::get('/news/{article}/{slug?}', function (string $locale, Article $article, ?string $slug = null) use ($pickLocalized) {
            $title = (string) ($pickLocalized($article, 'title', $locale) ?? ('news-' . $article->id));
            $expected = Str::slug($title);
            if ($slug !== $expected) {
                return redirect()->route('articles.show', ['article' => $article, 'slug' => $expected, 'locale' => $locale], 301);
            }

            // Increment views counter on each visit
            try {
                DB::table('articles')->where('id', $article->id)->increment('views');
            } catch (\Throwable $e) {
                // noop
            }

            return view('pages.article-show', compact('article'));
        })->name('articles.show');
    });
