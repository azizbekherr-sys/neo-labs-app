@extends('admin.layouts.app')
@section('title', 'Mahsulotlar')

@section('content')
  <header class="admin-page-header">
    <div><span class="admin-eyebrow"><i class="bi bi-box-seam" aria-hidden="true"></i>Kontent</span><h1 class="h3 mb-1">Mahsulotlar</h1><p>Mahsulot kontenti, nashr holati va media fayllarini boshqaring.</p></div>
    <a class="btn btn-primary" href="{{ route('dashboard.products.create') }}"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Yangi mahsulot</a>
  </header>

  <form class="admin-filter-bar mb-3" method="GET" action="{{ route('dashboard.products.index') }}" aria-label="Mahsulot filtrlari">
    <div class="row g-3 align-items-end">
      <div class="col-12 col-lg-4"><label class="form-label" for="product-search">Qidiruv</label><input class="form-control" id="product-search" name="q" value="{{ request('q') }}" placeholder="Nomi yoki shtrix-kod"></div>
      <div class="col-6 col-lg-2"><label class="form-label" for="product-status">Holati</label><select class="form-select" id="product-status" name="status"><option value="">Barchasi</option><option value="active" @selected(request('status')==='active')>Faol</option><option value="draft" @selected(request('status')==='draft')>Qoralama</option><option value="paused" @selected(request('status')==='paused')>To‘xtatilgan</option></select></div>
      <div class="col-6 col-lg-2"><label class="form-label" for="product-prescription-filter">Retsept</label><select class="form-select" id="product-prescription-filter" name="prescription"><option value="">Barchasi</option><option value="1" @selected(request('prescription')==='1')>Talab qilinadi</option><option value="0" @selected(request('prescription')==='0')>Talab qilinmaydi</option></select></div>
      <div class="col-12 col-lg-2"><label class="form-label" for="content-status">Kontent</label><select class="form-select" id="content-status" name="content_status"><option value="">Barchasi</option><option value="complete" @selected(request('content_status')==='complete')>To‘liq</option><option value="incomplete" @selected(request('content_status')==='incomplete')>To‘liq emas</option></select></div>
      <div class="col-12 col-lg-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit">Qidirish</button><a class="btn btn-outline-secondary" href="{{ route('dashboard.products.index') }}">Tozalash</a></div>
    </div>
    @if($activeFilterCount)<div class="mt-3"><span class="admin-filter-chip"><i class="bi bi-funnel" aria-hidden="true"></i>{{ $activeFilterCount }} ta faol filtr</span></div>@endif
  </form>

  <section class="admin-card" aria-labelledby="product-list-title">
    <div class="admin-card-header d-flex justify-content-between align-items-center"><h2 class="h5 mb-0" id="product-list-title">Mahsulotlar ro‘yxati</h2><span class="text-muted">{{ $products->total() }} ta natija</span></div>
    @if($products->count())
      <div class="table-responsive d-none d-lg-block"><table class="table admin-table"><thead><tr><th>Mahsulot</th><th>Turi</th><th>Kontent</th><th>Holati</th><th class="text-end">Amallar</th></tr></thead><tbody>
        @foreach($products as $product)
          @php
            $image = collect($product->images ?: [$product->image])->filter()->first();
            $plainDescription = trim(strip_tags(html_entity_decode((string) ($product->description_uz ?: $product->description), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
          @endphp
          <tr><td><div class="d-flex align-items-center gap-3">@if($image)<img class="admin-thumb" src="{{ media_url($image) }}" alt="{{ $product->name_uz ?: $product->name }} mahsuloti">@else<span class="admin-thumb d-grid place-items-center" aria-hidden="true"><i class="bi bi-image"></i></span>@endif<div><strong>{{ $product->name_uz ?: $product->name }}</strong><div class="small text-muted">{{ \Illuminate\Support\Str::limit($plainDescription, 70) ?: 'Tavsif kiritilmagan' }}</div></div></div></td><td>{{ $product->type_uz ?: '—' }}</td><td><x-admin.status-badge :status="$product->content_status ?: 'incomplete'" />@if($product->is_featured)<div class="small mt-1"><i class="bi bi-star-fill text-warning" aria-hidden="true"></i> Tanlangan</div>@endif @if($product->medical_review_required)<div class="small text-danger"><i class="bi bi-shield-exclamation" aria-hidden="true"></i> Tibbiy tekshiruv kerak</div>@endif</td><td><x-admin.status-badge :status="$product->status" /></td><td><div class="admin-actions"><a class="btn btn-outline-primary admin-icon-button" href="{{ route('dashboard.products.edit', $product) }}" aria-label="{{ $product->name_uz }} mahsulotini tahrirlash"><i class="bi bi-pencil" aria-hidden="true"></i></a><form id="delete-product-{{ $product->id }}" method="POST" action="{{ route('dashboard.products.destroy', $product) }}">@csrf @method('DELETE')</form><button class="btn btn-outline-danger admin-icon-button" type="button" data-confirm-delete data-form-id="delete-product-{{ $product->id }}" data-object-label="{{ $product->name_uz ?: $product->name }}" aria-label="{{ $product->name_uz }} mahsulotini o‘chirish"><i class="bi bi-trash" aria-hidden="true"></i></button></div></td></tr>
        @endforeach
      </tbody></table></div>
      <div class="admin-mobile-list p-3">
        @foreach($products as $product)
          @php $plainDescription = trim(strip_tags(html_entity_decode((string) ($product->description_uz ?: $product->description), ENT_QUOTES | ENT_HTML5, 'UTF-8'))); @endphp
          <article class="admin-mobile-card"><div class="d-flex justify-content-between gap-2"><div><h3 class="h6 mb-1">{{ $product->name_uz ?: $product->name }}</h3><p class="small text-muted mb-2">{{ \Illuminate\Support\Str::limit($plainDescription, 90) ?: 'Tavsif kiritilmagan' }}</p></div><x-admin.status-badge :status="$product->status" /></div><div class="d-flex align-items-center justify-content-between gap-2"><x-admin.status-badge :status="$product->content_status ?: 'incomplete'" /><div class="admin-actions"><a class="btn btn-outline-primary admin-icon-button" href="{{ route('dashboard.products.edit', $product) }}" aria-label="{{ $product->name_uz }} mahsulotini tahrirlash"><i class="bi bi-pencil" aria-hidden="true"></i></a><button class="btn btn-outline-danger admin-icon-button" type="button" data-confirm-delete data-form-id="delete-product-{{ $product->id }}" data-object-label="{{ $product->name_uz ?: $product->name }}" aria-label="{{ $product->name_uz }} mahsulotini o‘chirish"><i class="bi bi-trash" aria-hidden="true"></i></button></div></div></article>
        @endforeach
      </div>
      <div class="admin-card-body border-top">{{ $products->links() }}</div>
    @else
      <x-admin.empty-state icon="search" title="Mahsulot topilmadi" text="Qidiruv yoki filtrlarni o‘zgartirib ko‘ring."><a class="btn btn-outline-primary mt-2" href="{{ route('dashboard.products.index') }}">Filtrlarni tozalash</a></x-admin.empty-state>
    @endif
  </section>

@endsection
