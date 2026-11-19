@extends('admin.layouts.app')
@section('title', 'Yangi xat — Pochta')

@section('content')
  <header class="admin-page-header">
    <div>
      <span class="admin-eyebrow"><i class="bi bi-pencil-square" aria-hidden="true"></i>Pochta</span>
      <h1 class="h3 mb-1">Yangi xat</h1>
      <p>Xat <strong>{{ $address }}</strong> manzilidan yuboriladi va "Yuborilgan" papkasida saqlanadi.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('dashboard.mail.index') }}"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Orqaga</a>
  </header>

  <section class="admin-card" aria-label="Yangi xat formasi">
    <div class="admin-card-body">
      <form method="POST" action="{{ route('dashboard.mail.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
          <label class="form-label" for="mail-to">Kimga (email)</label>
          <input class="form-control @error('to') is-invalid @enderror" type="email" id="mail-to" name="to" value="{{ old('to', $to) }}" maxlength="255" required autocomplete="off">
          @error('to')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
          <label class="form-label" for="mail-subject">Mavzu</label>
          <input class="form-control @error('subject') is-invalid @enderror" id="mail-subject" name="subject" value="{{ old('subject') }}" maxlength="200" required>
          @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
          <label class="form-label" for="mail-body">Xat matni</label>
          <textarea class="form-control @error('body') is-invalid @enderror" id="mail-body" name="body" rows="10" maxlength="20000" required>{{ old('body') }}</textarea>
          @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
          <label class="form-label" for="mail-attachments">Fayl biriktirish <span class="text-muted small">(ixtiyoriy, 5 tagacha, har biri 10 MB gacha)</span></label>
          <input class="form-control admin-file-input @error('attachments.*') is-invalid @enderror" type="file" id="mail-attachments" name="attachments[]" multiple aria-describedby="mail-attachments-help">
          <div class="form-text" id="mail-attachments-help">Fayl tanlanmagan</div>
          @error('attachments.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button class="btn btn-primary" type="submit"><i class="bi bi-send me-2" aria-hidden="true"></i>Yuborish</button>
      </form>
    </div>
  </section>
@endsection
