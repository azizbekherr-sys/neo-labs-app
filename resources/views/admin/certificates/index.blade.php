@extends('admin.layouts.app')
@section('title', 'Sertifikatlar')

@section('content')
  <header class="admin-page-header">
    <div>
      <span class="admin-eyebrow"><i class="bi bi-patch-check" aria-hidden="true"></i>Kontent</span>
      <h1 class="h3 mb-1">Sertifikatlar</h1>
      <p>Sifat va muvofiqlik sertifikatlarini boshqaring va saytda e’lon qiling.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <span class="admin-filter-chip"><i class="bi bi-collection" aria-hidden="true"></i>{{ $certificates->total() }} ta jami</span>
      <span class="admin-filter-chip"><i class="bi bi-eye" aria-hidden="true"></i>{{ $publishedCount }} ta e’lon qilingan</span>
    </div>
  </header>

  <details class="admin-disclosure mb-4" @if($openForm ?? false) open @endif>
    <summary>
      <span class="d-flex align-items-center gap-2"><span class="admin-form-step"><i class="bi bi-plus-lg" aria-hidden="true"></i></span><span><strong>Yangi sertifikat qo‘shish</strong><small class="d-block">Uch tilda nom, raqam va tasdiqlovchi hujjat</small></span></span>
      <i class="bi bi-chevron-down" aria-hidden="true"></i>
    </summary>
    <div class="admin-disclosure__body pt-3">@include('admin.certificates.partials.form', ['certificate' => null])</div>
  </details>

  <section aria-labelledby="certificate-list-title">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 mb-0" id="certificate-list-title">Sertifikatlar ro‘yxati</h2>
      <span class="text-muted">{{ $certificates->total() }} ta natija</span>
    </div>
    @if($certificates->count())
      <div class="row g-3">
        @foreach($certificates as $certificate)
          <div class="col-12 col-xl-6">
            <details class="admin-disclosure h-100">
              <summary>
                <span class="d-flex align-items-center gap-3">
                  <span class="admin-stat-icon"><i class="bi bi-patch-check" aria-hidden="true"></i></span>
                  <span><strong>{{ $certificate->name_uz }}</strong><small class="d-block">{{ $certificate->number ?: 'Raqamsiz' }}</small></span>
                </span>
                <span class="d-flex align-items-center gap-2">
                  @if($certificate->is_published)<span class="admin-status admin-status-success"><i class="bi bi-eye" aria-hidden="true"></i>E’lon qilingan</span>@else<span class="admin-status admin-status-secondary"><i class="bi bi-eye-slash" aria-hidden="true"></i>Qoralama</span>@endif
                  <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </span>
              </summary>
              <div class="admin-disclosure__body pt-3">
                @include('admin.certificates.partials.form', ['certificate' => $certificate])
                <form id="delete-certificate-{{ $certificate->id }}" method="POST" action="{{ route('dashboard.certificates.destroy', $certificate) }}" class="mt-2">@csrf @method('DELETE')</form>
                <button class="btn btn-outline-danger mt-2" type="button" data-confirm-delete data-form-id="delete-certificate-{{ $certificate->id }}" data-object-label="{{ $certificate->name_uz }}" aria-label="{{ $certificate->name_uz }} sertifikatini o‘chirish"><i class="bi bi-trash me-2" aria-hidden="true"></i>O‘chirish</button>
              </div>
            </details>
          </div>
        @endforeach
      </div>
      <div class="mt-4">{{ $certificates->links() }}</div>
    @else
      <x-admin.empty-state class="admin-card" icon="patch-check" title="Sertifikatlar yo‘q" text="Yuqoridagi forma orqali birinchi sertifikatni qo‘shing." />
    @endif
  </section>
@endsection
