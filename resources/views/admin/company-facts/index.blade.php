@extends('admin.layouts.app')
@section('title', 'Kompaniya faktlari')

@section('content')
  <header class="admin-page-header">
    <div>
      <span class="admin-eyebrow"><i class="bi bi-building-check" aria-hidden="true"></i>Kontent</span>
      <h1 class="h3 mb-1">Kompaniya faktlari</h1>
      <p>Saytda ko‘rsatiladigan tasdiqlangan kompaniya ko‘rsatkichlari va faktlari.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <span class="admin-filter-chip"><i class="bi bi-collection" aria-hidden="true"></i>{{ $companyFacts->total() }} ta jami</span>
      <span class="admin-filter-chip"><i class="bi bi-eye" aria-hidden="true"></i>{{ $publishedCount }} ta e’lon qilingan</span>
    </div>
  </header>

  <details class="admin-disclosure mb-4" @if($openForm ?? false) open @endif>
    <summary>
      <span class="d-flex align-items-center gap-2"><span class="admin-form-step"><i class="bi bi-plus-lg" aria-hidden="true"></i></span><span><strong>Yangi fakt qo‘shish</strong><small class="d-block">Kalit, uch tilli sarlavha va qiymat</small></span></span>
      <i class="bi bi-chevron-down" aria-hidden="true"></i>
    </summary>
    <div class="admin-disclosure__body pt-3">@include('admin.company-facts.partials.form', ['fact' => null])</div>
  </details>

  <section aria-labelledby="fact-list-title">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 mb-0" id="fact-list-title">Faktlar ro‘yxati</h2>
      <span class="text-muted">{{ $companyFacts->total() }} ta natija</span>
    </div>
    @if($companyFacts->count())
      <div class="row g-3">
        @foreach($companyFacts as $fact)
          <div class="col-12 col-xl-6">
            <details class="admin-disclosure h-100">
              <summary>
                <span class="d-flex align-items-center gap-3">
                  <span class="admin-stat-icon"><i class="bi bi-building-check" aria-hidden="true"></i></span>
                  <span><strong>{{ $fact->label_uz }}</strong><small class="d-block"><code>{{ $fact->key }}</code></small></span>
                </span>
                <span class="d-flex align-items-center gap-2">
                  @if($fact->is_published)<span class="admin-status admin-status-success"><i class="bi bi-eye" aria-hidden="true"></i>E’lon qilingan</span>@else<span class="admin-status admin-status-secondary"><i class="bi bi-eye-slash" aria-hidden="true"></i>Qoralama</span>@endif
                  <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </span>
              </summary>
              <div class="admin-disclosure__body pt-3">
                @include('admin.company-facts.partials.form', ['fact' => $fact])
                <form id="delete-fact-{{ $fact->id }}" method="POST" action="{{ route('dashboard.company-facts.destroy', $fact) }}" class="mt-2">@csrf @method('DELETE')</form>
                <button class="btn btn-outline-danger mt-2" type="button" data-confirm-delete data-form-id="delete-fact-{{ $fact->id }}" data-object-label="{{ $fact->label_uz }}" aria-label="{{ $fact->label_uz }} faktini o‘chirish"><i class="bi bi-trash me-2" aria-hidden="true"></i>O‘chirish</button>
              </div>
            </details>
          </div>
        @endforeach
      </div>
      <div class="mt-4">{{ $companyFacts->links() }}</div>
    @else
      <x-admin.empty-state class="admin-card" icon="building-check" title="Kompaniya faktlari yo‘q" text="Yuqoridagi forma orqali birinchi faktni qo‘shing." />
    @endif
  </section>
@endsection
