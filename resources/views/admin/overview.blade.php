@extends('admin.layouts.app')
@section('title', 'Umumiy ko‘rinish')

@section('content')
  <header class="admin-page-header">
    <div>
      <span class="admin-eyebrow"><i class="bi bi-grid-1x2" aria-hidden="true"></i>Boshqaruv paneli</span>
      <h1 class="h3 mb-1">Umumiy ko‘rinish</h1>
      <p>Sayt kontenti va murojaatlarning qisqacha holati.</p>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-primary" href="{{ route('dashboard.products.create') }}"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Mahsulot qo‘shish</a>
      <a class="btn btn-outline-primary" href="{{ route('dashboard.articles.index') }}"><i class="bi bi-newspaper me-2" aria-hidden="true"></i>Maqola qo‘shish</a>
    </div>
  </header>

  <section aria-labelledby="statistics-title" class="mb-4">
    <h2 class="visually-hidden" id="statistics-title">Asosiy ko‘rsatkichlar</h2>
    <div class="row g-3">
      <div class="col-6 col-lg-4 col-xl-3"><x-admin.stat-card :href="route('dashboard.products.index')" label="Jami mahsulot" :value="$stats['products']" icon="box-seam" /></div>
      <div class="col-6 col-lg-4 col-xl-3"><x-admin.stat-card :href="route('dashboard.products.index', ['status'=>'active'])" label="Faol mahsulot" :value="$stats['active_products']" icon="check-circle" tone="success" /></div>
      <div class="col-6 col-lg-4 col-xl-3"><x-admin.stat-card :href="route('dashboard.articles.index')" label="Maqolalar" :value="$stats['articles']" icon="newspaper" /></div>
      <div class="col-6 col-lg-4 col-xl-3"><x-admin.stat-card :href="route('dashboard.partners.index')" label="Hamkorlar" :value="$stats['partners']" icon="people" /></div>
      <div class="col-6 col-lg-4 col-xl-3"><x-admin.stat-card :href="route('dashboard.certificates.index')" label="Sertifikatlar" :value="$stats['certificates']" icon="patch-check" /></div>
      <div class="col-6 col-lg-4 col-xl-3"><x-admin.stat-card :href="route('dashboard.company-facts.index')" label="Kompaniya faktlari" :value="$stats['company_facts']" icon="building-check" /></div>
      <div class="col-6 col-lg-4 col-xl-3"><x-admin.stat-card :href="route('dashboard.messages.index', ['status'=>'new'])" label="Yangi murojaatlar" :value="$stats['new_messages']" icon="chat-left-dots" :tone="$stats['new_messages'] > 0 ? 'warning' : 'success'" /></div>
      <div class="col-6 col-lg-4 col-xl-3"><x-admin.stat-card :href="url('/uz')" label="Saytni ochish" value="↗" icon="globe" /></div>
    </div>
  </section>

  <div class="row g-4">
    <section class="col-12 col-xl-6" aria-labelledby="recent-messages-title">
      <div class="admin-card h-100">
        <div class="admin-card-header d-flex justify-content-between align-items-center"><h2 class="h5 mb-0" id="recent-messages-title">Oxirgi murojaatlar</h2><a href="{{ route('dashboard.messages.index') }}">Barchasi</a></div>
        <div class="admin-card-body">
          @forelse($recentMessages as $message)
            <div class="d-flex justify-content-between gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
              <div><strong>{{ $message->name }}</strong><div class="small text-muted">{{ \Illuminate\Support\Str::limit($message->message, 70) }}</div></div>
              <div class="text-end"><x-admin.status-badge :status="$message->status" /><div class="small text-muted mt-1">{{ optional($message->created_at)->format('d.m H:i') }}</div></div>
            </div>
          @empty<x-admin.empty-state icon="chat-left-dots" title="Murojaatlar yo‘q" text="Yangi murojaatlar shu yerda ko‘rinadi." />@endforelse
        </div>
      </div>
    </section>
    <section class="col-12 col-xl-6" aria-labelledby="recent-products-title">
      <div class="admin-card h-100">
        <div class="admin-card-header d-flex justify-content-between align-items-center"><h2 class="h5 mb-0" id="recent-products-title">Oxirgi mahsulotlar</h2><a href="{{ route('dashboard.products.index') }}">Barchasi</a></div>
        <div class="admin-card-body">
          @forelse($recentProducts as $product)
            <div class="d-flex justify-content-between align-items-center gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
              <div><strong>{{ $product->name_uz ?: $product->name }}</strong><div class="small text-muted">{{ $product->barcode ?: 'Shtrix-kod yo‘q' }}</div></div>
              <x-admin.status-badge :status="$product->status" />
            </div>
          @empty<x-admin.empty-state icon="box-seam" title="Mahsulotlar yo‘q" text="Birinchi mahsulotni qo‘shishingiz mumkin." />@endforelse
        </div>
      </div>
    </section>
    <section class="col-12" aria-labelledby="recent-articles-title">
      <div class="admin-card">
        <div class="admin-card-header d-flex justify-content-between align-items-center"><h2 class="h5 mb-0" id="recent-articles-title">Oxirgi maqolalar</h2><a href="{{ route('dashboard.articles.index') }}">Barchasi</a></div>
        <div class="admin-card-body row g-3">
          @forelse($recentArticles as $article)
            <div class="col-12 col-md-6 col-xl"><a class="d-block p-3 border rounded-3 text-decoration-none h-100" href="{{ route('dashboard.articles.edit', $article) }}"><strong class="d-block" style="color:var(--admin-heading)">{{ $article->title_uz }}</strong><span class="d-block small text-muted mt-1">{{ optional($article->created_at)->format('d.m.Y') }}</span></a></div>
          @empty<div class="col-12"><x-admin.empty-state icon="newspaper" title="Maqolalar yo‘q" /></div>@endforelse
        </div>
      </div>
    </section>
  </div>
@endsection
