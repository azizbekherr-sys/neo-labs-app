@extends('admin.layouts.app')
@section('title', 'Hamkorlar')

@section('content')
  <header class="admin-page-header"><div><span class="admin-eyebrow"><i class="bi bi-people" aria-hidden="true"></i>Kontent</span><h1 class="h3 mb-1">Hamkorlar</h1><p>Hamkor logotiplari va tashqi havolalarini boshqaring.</p></div></header>
  <section class="admin-card mb-4" aria-labelledby="partner-add-title"><div class="admin-card-header"><h2 class="h5 mb-0" id="partner-add-title">Yangi hamkor</h2></div><div class="admin-card-body"><form method="POST" action="{{ route('dashboard.partners.store') }}" enctype="multipart/form-data"><div class="row g-3 align-items-end">@csrf
    <div class="col-12 col-md-5"><label class="form-label" for="new-partner-logo">Logotip <span class="required-mark" aria-hidden="true">*</span><span class="visually-hidden">majburiy</span></label><input required class="form-control admin-file-input @error('partners.0.logo') is-invalid @enderror" id="new-partner-logo" type="file" name="partners[0][logo]" accept="image/jpeg,image/png,image/webp,image/svg+xml" aria-describedby="new-partner-logo-help"><span class="admin-file-help" id="new-partner-logo-help">Fayl tanlanmagan. SVG, PNG, JPG yoki WebP.</span></div>
    <div class="col-12 col-md-5"><label class="form-label" for="new-partner-url">Hamkor sayti</label><input class="form-control" id="new-partner-url" type="url" name="partners[0][url]" value="{{ old('partners.0.url') }}" placeholder="https://example.uz"></div>
    <div class="col-12 col-md-2"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-upload me-2" aria-hidden="true"></i>Yuklash</button></div>
  </div></form></div></section>

  <section aria-labelledby="partner-list-title"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0" id="partner-list-title">Hamkorlar ro‘yxati</h2><span class="text-muted">{{ $partners->total() }} ta hamkor</span></div>
    @if($partners->count())<div class="row g-3">@foreach($partners as $partner)<div class="col-12 col-md-6 col-xl-4"><article class="admin-card h-100"><div class="admin-card-body">
      <div class="d-grid place-items-center border rounded-3 p-3 mb-3" style="min-height:110px">@if($partner->url)<a href="{{ $partner->url }}" target="_blank" rel="noopener" aria-label="Hamkor saytini yangi oynada ochish"><img src="{{ asset($partner->path) }}" alt="Hamkor logotipi" style="max-width:180px;max-height:72px;object-fit:contain"></a>@else<img src="{{ asset($partner->path) }}" alt="Hamkor logotipi" style="max-width:180px;max-height:72px;object-fit:contain">@endif</div>
      <form method="POST" action="{{ route('dashboard.partners.update',$partner) }}" enctype="multipart/form-data" class="vstack gap-2">@csrf @method('PATCH')
        <label class="form-label mb-0" for="partner-url-{{ $partner->id }}">Sayt havolasi</label><input class="form-control" id="partner-url-{{ $partner->id }}" type="url" name="url" value="{{ $partner->url }}">
        <label class="form-label mb-0" for="partner-logo-{{ $partner->id }}">Logotipni almashtirish</label><input class="form-control admin-file-input" id="partner-logo-{{ $partner->id }}" type="file" name="logo" accept="image/*" aria-describedby="partner-logo-help-{{ $partner->id }}"><span class="admin-file-help" id="partner-logo-help-{{ $partner->id }}">Fayl tanlanmagan</span>
        <button class="btn btn-primary" type="submit">Yangilash</button>
      </form>
      <form id="delete-partner-{{ $partner->id }}" method="POST" action="{{ route('dashboard.partners.destroy',$partner) }}">@csrf @method('DELETE')</form><button class="btn btn-outline-danger w-100 mt-2" type="button" data-confirm-delete data-form-id="delete-partner-{{ $partner->id }}" data-object-label="Hamkor #{{ $partner->id }}" aria-label="Hamkor logotipini o‘chirish"><i class="bi bi-trash me-2" aria-hidden="true"></i>O‘chirish</button>
    </div></article></div>@endforeach</div><div class="mt-4">{{ $partners->links() }}</div>@else<x-admin.empty-state class="admin-card" icon="people" title="Hamkorlar yo‘q" text="Yuqoridagi forma orqali birinchi hamkorni qo‘shing." />@endif
  </section>
@endsection
