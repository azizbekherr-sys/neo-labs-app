<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Certificate;
use App\Models\CompanyFact;
use App\Models\ContactMessage;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        return view('admin.overview', [
            'stats' => $this->stats(),
            'recentProducts' => Product::query()->latest()->take(5)->get(),
            'recentArticles' => Article::query()->latest()->take(5)->get(),
            'recentMessages' => ContactMessage::query()->latest()->take(5)->get(),
        ]);
    }

    public function products(Request $request)
    {
        $query = Product::query()->latest('id');

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('name_uz', 'like', "%{$search}%")
                    ->orWhere('name_ru', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if (in_array($request->input('status'), ['active', 'draft', 'paused'], true)) {
            $query->where('status', $request->input('status'));
        }
        if (in_array((string) $request->input('prescription'), ['0', '1'], true)) {
            $query->where('prescription', $request->input('prescription') === '1');
        }
        if (in_array($request->input('content_status'), ['complete', 'incomplete'], true)) {
            $query->where('content_status', $request->input('content_status'));
        }

        return view('admin.products.index', [
            'products' => $query->paginate(12)->withQueryString(),
            'productCategories' => ProductCategory::query()->orderBy('name_uz')->get(),
            'productOptions' => collect(),
            'activeFilterCount' => collect(['q', 'status', 'prescription', 'content_status'])
                ->filter(fn ($key) => $request->filled($key))->count(),
            'openModal' => null,
        ]);
    }

    public function articles()
    {
        return view('admin.articles.index', [
            'articles' => Article::query()->latest('id')->paginate(12)->withQueryString(),
            'openModal' => null,
        ]);
    }

    public function partners()
    {
        return view('admin.partners.index', [
            'partners' => Partner::query()->orderBy('id')->paginate(18)->withQueryString(),
        ]);
    }

    public function certificates()
    {
        return view('admin.certificates.index', [
            'certificates' => Certificate::query()->latest('id')->paginate(12)->withQueryString(),
            'publishedCount' => Certificate::query()->where('is_published', true)->count(),
            'openForm' => $this->hasCertificateErrors(),
        ]);
    }

    public function companyFacts()
    {
        return view('admin.company-facts.index', [
            'companyFacts' => CompanyFact::query()->orderBy('key')->paginate(12)->withQueryString(),
            'publishedCount' => CompanyFact::query()->where('is_published', true)->count(),
            'openForm' => $this->hasFactErrors(),
        ]);
    }

    private function hasCertificateErrors(): bool
    {
        return session()->has('errors')
            && session('errors')->hasAny(['name_uz', 'name_ru', 'name_en', 'number', 'verification_url']);
    }

    private function hasFactErrors(): bool
    {
        return session()->has('errors')
            && session('errors')->hasAny(['key', 'label_uz', 'label_ru', 'label_en', 'value_uz', 'value_ru', 'value_en']);
    }

    public function messages(Request $request)
    {
        $query = ContactMessage::query()->latest('id');

        if (in_array($request->input('status'), ['new', 'read', 'closed'], true)) {
            $query->where('status', $request->input('status'));
        }
        if (in_array($request->input('context'), ['general', 'manufacturing'], true)) {
            $query->where('context', $request->input('context'));
        }
        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('contact', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        return view('admin.messages.index', [
            'messages' => $query->paginate(20)->withQueryString(),
            'newMessageCount' => ContactMessage::query()->where('status', 'new')->count(),
        ]);
    }

    private function stats(): array
    {
        // Single round-trip instead of 7 separate COUNT queries — matters a lot
        // when the DB is remote (each query is a full round-trip).
        $row = \Illuminate\Support\Facades\DB::selectOne("
            select
                (select count(*) from products) as products,
                (select count(*) from products where status = 'active') as active_products,
                (select count(*) from articles) as articles,
                (select count(*) from partners) as partners,
                (select count(*) from certificates) as certificates,
                (select count(*) from company_facts) as company_facts,
                (select count(*) from contact_messages where status = 'new') as new_messages
        ");

        return [
            'products' => (int) $row->products,
            'active_products' => (int) $row->active_products,
            'articles' => (int) $row->articles,
            'partners' => (int) $row->partners,
            'certificates' => (int) $row->certificates,
            'company_facts' => (int) $row->company_facts,
            'new_messages' => (int) $row->new_messages,
        ];
    }
}
