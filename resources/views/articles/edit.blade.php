@extends('admin.layouts.app')
@section('title', 'Maqolani tahrirlash')

@php
  $articleLocales = ['uz' => 'O‘zbek', 'ru' => 'Русский', 'en' => 'English'];
  $currentPhoto = $article->photo ? media_url($article->photo) : null;
@endphp

@section('content')
  <div
    data-article-ai-root
    data-endpoint-import="{{ route('dashboard.articles.ai.import') }}"
    data-endpoint-translate="{{ route('dashboard.articles.ai.translate') }}"
    data-endpoint-seo="{{ route('dashboard.articles.ai.seo') }}"
    data-endpoint-image="{{ route('dashboard.articles.ai.image') }}"
  >
    <header class="admin-page-header">
      <div>
        <span class="admin-eyebrow"><i class="bi bi-newspaper" aria-hidden="true"></i>Maqolalar</span>
        <h1 class="h3 mb-1">{{ $article->title_uz }}</h1>
        <p>Uch tildagi kontent, mualliflik va SEO ma’lumotlarini tahrirlang. AI qadamlaridan ham foydalanishingiz mumkin.</p>
      </div>
      <a class="btn btn-outline-secondary" href="{{ route('dashboard.articles.index') }}"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Orqaga</a>
    </header>

    {{-- Optional: re-import from a link --}}
    <section class="admin-ai-panel mb-4" aria-labelledby="edit-ai-title">
      <div class="admin-ai-panel__head">
        <span class="admin-ai-icon" aria-hidden="true"><i class="bi bi-stars"></i></span>
        <div>
          <h2 class="h5 mb-1" id="edit-ai-title">AI yordamchisi</h2>
          <p class="small text-muted mb-0">Havoladan qayta tayyorlash, tarjima, SEO va rasm — har biri alohida. Mavjud matn ustiga yozishdan oldin so‘raladi.</p>
        </div>
      </div>
      <div class="admin-ai-import mt-3">
        <label class="form-label fw-semibold" for="edit-ai-url">Maqola havolasi (ixtiyoriy)</label>
        <div class="admin-ai-import__row">
          <input type="url" id="edit-ai-url" data-ai-url class="form-control" placeholder="https://example.com/maqola-havolasi" autocomplete="off">
          <button type="button" class="btn btn-primary admin-ai-btn" data-ai-action="import">
            <i class="bi bi-magic me-2" aria-hidden="true"></i><span class="admin-ai-btn__label">AI orqali maqolani tayyorlash</span>
          </button>
        </div>
        <span class="admin-ai-badge" data-ai-lang-badge hidden></span>
      </div>
    </section>

    <form method="POST" action="{{ route('dashboard.articles.update', $article) }}" enctype="multipart/form-data" class="vstack gap-4" novalidate>
      @csrf @method('PUT')
      <input type="hidden" name="photo_path" data-ai-photo-path>

      <section class="admin-form-section" aria-labelledby="edit-content-heading" data-editor-modal>
        <div class="admin-form-section__head"><span class="admin-form-step">1</span><div><h2 class="h5 mb-1" id="edit-content-heading">Maqola matni — UZ / RU / EN</h2><p class="text-muted mb-0 small">Har bir til uchun sarlavha, matn va (pastda) SEO maydonlari.</p></div></div>

        <ul class="nav nav-tabs" role="tablist">
          @foreach($articleLocales as $locale => $localeLabel)
            <li class="nav-item"><button class="nav-link {{ $locale==='uz'?'active':'' }}" id="edit-{{ $locale }}-tab" data-ai-tab="{{ $locale }}" data-bs-toggle="tab" data-bs-target="#edit-{{ $locale }}-pane" type="button" role="tab" aria-controls="edit-{{ $locale }}-pane" aria-selected="{{ $locale==='uz'?'true':'false' }}">{{ $localeLabel }}</button></li>
          @endforeach
        </ul>

        <div class="tab-content border border-top-0 rounded-bottom p-3">
          @foreach($articleLocales as $locale => $localeLabel)
            @php $kw = $article->{'keywords_'.$locale} ?: []; @endphp
            <div class="tab-pane fade {{ $locale==='uz' ? 'show active' : '' }}" id="edit-{{ $locale }}-pane" role="tabpanel" aria-labelledby="edit-{{ $locale }}-tab" tabindex="0"><div class="row g-3">
              <div class="col-12"><label class="form-label" for="edit-title-{{ $locale }}">Sarlavha ({{ strtoupper($locale) }}) <span class="required-mark" aria-hidden="true">*</span></label><input required id="edit-title-{{ $locale }}" name="title_{{ $locale }}" class="form-control" value="{{ old('title_'.$locale, $article->{'title_'.$locale}) }}"></div>
              <div class="col-12"><label class="form-label" for="edit-desc-{{ $locale }}">Maqola matni ({{ strtoupper($locale) }})</label><textarea id="edit-desc-{{ $locale }}" name="description_{{ $locale }}" rows="10" class="form-control" data-editor>{{ old('description_'.$locale, $article->{'description_'.$locale}) }}</textarea></div>

              <div class="col-12">
                <details class="admin-seo-details">
                  <summary><i class="bi bi-graph-up-arrow me-2" aria-hidden="true"></i>SEO ({{ strtoupper($locale) }})</summary>
                  <div class="row g-3 mt-1">
                    <div class="col-12 col-lg-6"><label class="form-label" for="edit-seo-{{ $locale }}">SEO title <small class="text-muted">(≈50–60)</small></label><input id="edit-seo-{{ $locale }}" name="seo_title_{{ $locale }}" class="form-control" value="{{ old('seo_title_'.$locale, $article->{'seo_title_'.$locale}) }}"></div>
                    <div class="col-12 col-lg-6"><label class="form-label" for="edit-slug-{{ $locale }}">URL slug</label><input id="edit-slug-{{ $locale }}" name="slug_{{ $locale }}" class="form-control" value="{{ old('slug_'.$locale, $article->{'slug_'.$locale}) }}"></div>
                    <div class="col-12"><label class="form-label" for="edit-meta-{{ $locale }}">Meta description <small class="text-muted">(≈140–160)</small></label><textarea id="edit-meta-{{ $locale }}" name="meta_description_{{ $locale }}" class="form-control" rows="2">{{ old('meta_description_'.$locale, $article->{'meta_description_'.$locale}) }}</textarea></div>
                    <div class="col-12 col-lg-4"><label class="form-label" for="edit-focus-{{ $locale }}">Asosiy kalit so‘z</label><input id="edit-focus-{{ $locale }}" name="focus_keyword_{{ $locale }}" class="form-control" value="{{ old('focus_keyword_'.$locale, $article->{'focus_keyword_'.$locale}) }}"></div>
                    <div class="col-12 col-lg-8"><label class="form-label" for="edit-keywords-{{ $locale }}">Kalit so‘zlar <small class="text-muted">(vergul bilan)</small></label><input id="edit-keywords-{{ $locale }}" name="keywords_{{ $locale }}_text" class="form-control" value="{{ old('keywords_'.$locale.'_text', implode(', ', $kw)) }}"></div>
                    <div class="col-12 col-lg-6"><label class="form-label" for="edit-ogtitle-{{ $locale }}">Open Graph title</label><input id="edit-ogtitle-{{ $locale }}" name="og_title_{{ $locale }}" class="form-control" value="{{ old('og_title_'.$locale, $article->{'og_title_'.$locale}) }}"></div>
                    <div class="col-12 col-lg-6"><label class="form-label" for="edit-ogdesc-{{ $locale }}">Open Graph description</label><input id="edit-ogdesc-{{ $locale }}" name="og_description_{{ $locale }}" class="form-control" value="{{ old('og_description_'.$locale, $article->{'og_description_'.$locale}) }}"></div>
                    <div class="col-12 col-lg-8"><label class="form-label" for="edit-schema-{{ $locale }}">Qisqa tavsif (schema)</label><textarea id="edit-schema-{{ $locale }}" name="schema_description_{{ $locale }}" class="form-control" rows="2">{{ old('schema_description_'.$locale, $article->{'schema_description_'.$locale}) }}</textarea></div>
                    <div class="col-12 col-lg-4"><label class="form-label" for="edit-alt-{{ $locale }}">Rasm ALT matni</label><input id="edit-alt-{{ $locale }}" name="image_alt_{{ $locale }}" class="form-control" value="{{ old('image_alt_'.$locale, $article->{'image_alt_'.$locale}) }}"></div>
                  </div>
                </details>
              </div>

              <div class="col-12"><label class="form-label" for="edit-refs-{{ $locale }}">Manbalar — har qatorda bittadan</label><textarea id="edit-refs-{{ $locale }}" name="references_{{ $locale }}_text" rows="2" class="form-control">{{ old('references_'.$locale.'_text', implode("\n", $article->{'references_'.$locale} ?: [])) }}</textarea></div>
              @php $faqs = $article->{'faqs_'.$locale} ?: [['question' => '', 'answer' => '']]; @endphp
              @foreach($faqs as $faq)
                <div class="col-12 col-md-6"><label class="form-label" for="edit-faq-q-{{ $locale }}-{{ $loop->index }}">FAQ savol</label><input id="edit-faq-q-{{ $locale }}-{{ $loop->index }}" name="faq_questions_{{ $locale }}[]" class="form-control" value="{{ $faq['question'] ?? '' }}"></div>
                <div class="col-12 col-md-6"><label class="form-label" for="edit-faq-a-{{ $locale }}-{{ $loop->index }}">FAQ javob</label><textarea id="edit-faq-a-{{ $locale }}-{{ $loop->index }}" name="faq_answers_{{ $locale }}[]" class="form-control" rows="2">{{ $faq['answer'] ?? '' }}</textarea></div>
              @endforeach
            </div></div>
          @endforeach
        </div>

        <div class="admin-ai-actions mt-3">
          <button type="button" class="btn btn-outline-primary admin-ai-btn" data-ai-action="translate">
            <i class="bi bi-translate me-2" aria-hidden="true"></i><span class="admin-ai-btn__label">AI orqali tarjima qilish</span>
          </button>
          <button type="button" class="btn btn-outline-primary admin-ai-btn" data-ai-action="seo">
            <i class="bi bi-graph-up-arrow me-2" aria-hidden="true"></i><span class="admin-ai-btn__label">AI orqali SEO yaratish</span>
          </button>
          <span class="admin-ai-hint"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Mavjud matn ustiga yozishdan oldin so‘raladi.</span>
        </div>
      </section>

      <section class="admin-form-section" aria-labelledby="edit-people-heading">
        <div class="admin-form-section__head"><span class="admin-form-step">2</span><div><h2 class="h5 mb-1" id="edit-people-heading">Muallif va tekshiruv</h2><p class="text-muted mb-0 small">E-E-A-T uchun mualliflik va tibbiy tekshiruv ma’lumotlari.</p></div></div>
        <div class="row g-3">
          <div class="col-12 col-md-6"><label class="form-label" for="edit-author">Muallif</label><input id="edit-author" name="author_name" class="form-control" value="{{ old('author_name', $article->author_name) }}"></div>
          <div class="col-6 col-md-2"><label class="form-label" for="edit-author-role-uz">Lavozim UZ</label><input id="edit-author-role-uz" name="author_role_uz" class="form-control" value="{{ old('author_role_uz', $article->author_role_uz) }}"></div>
          <div class="col-6 col-md-2"><label class="form-label" for="edit-author-role-ru">Должность RU</label><input id="edit-author-role-ru" name="author_role_ru" class="form-control" value="{{ old('author_role_ru', $article->author_role_ru) }}"></div>
          <div class="col-6 col-md-2"><label class="form-label" for="edit-author-role-en">Role EN</label><input id="edit-author-role-en" name="author_role_en" class="form-control" value="{{ old('author_role_en', $article->author_role_en) }}"></div>
          <div class="col-12 col-md-6"><label class="form-label" for="edit-reviewer">Tekshiruvchi (real tekshiruv bo‘lsa)</label><input id="edit-reviewer" name="reviewer_name" class="form-control" value="{{ old('reviewer_name', $article->reviewer_name) }}"></div>
          <div class="col-6 col-md-2"><label class="form-label" for="edit-reviewer-role-uz">Reviewer UZ</label><input id="edit-reviewer-role-uz" name="reviewer_role_uz" class="form-control" value="{{ old('reviewer_role_uz', $article->reviewer_role_uz) }}"></div>
          <div class="col-6 col-md-2"><label class="form-label" for="edit-reviewer-role-ru">Reviewer RU</label><input id="edit-reviewer-role-ru" name="reviewer_role_ru" class="form-control" value="{{ old('reviewer_role_ru', $article->reviewer_role_ru) }}"></div>
          <div class="col-6 col-md-2"><label class="form-label" for="edit-reviewer-role-en">Reviewer EN</label><input id="edit-reviewer-role-en" name="reviewer_role_en" class="form-control" value="{{ old('reviewer_role_en', $article->reviewer_role_en) }}"></div>
          <div class="col-12 col-md-4"><label class="form-label" for="edit-reviewed-at">Tekshirilgan sana</label><input id="edit-reviewed-at" type="date" name="reviewed_at" class="form-control" value="{{ old('reviewed_at', optional($article->reviewed_at)->toDateString()) }}"></div>
        </div>
      </section>

      <section class="admin-form-section" aria-labelledby="edit-seo-heading">
        <div class="admin-form-section__head"><span class="admin-form-step">3</span><div><h2 class="h5 mb-1" id="edit-seo-heading">Asosiy rasm va nashr</h2><p class="text-muted mb-0 small">AI rasm tanlaydi yoki qo‘lda yuklaysiz; schema, robots, canonical.</p></div></div>

        <div class="admin-ai-media">
          <div class="admin-ai-media__preview" data-ai-image @if(!$currentPhoto) hidden @endif>
            <img alt="Tanlangan rasm" data-ai-image-img src="{{ $currentPhoto }}">
            <span class="admin-ai-media__tag"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Saqlaganda shu rasm qo‘yiladi</span>
          </div>
          <div class="admin-ai-media__controls">
            <button type="button" class="btn btn-primary admin-ai-btn" data-ai-action="image">
              <i class="bi bi-image me-2" aria-hidden="true"></i><span class="admin-ai-btn__label">AI orqali rasm tanlash</span>
            </button>
            <button type="button" class="btn btn-outline-primary admin-ai-btn" data-ai-action="image-more" @if(!$currentPhoto) hidden @endif>
              <i class="bi bi-arrow-repeat me-2" aria-hidden="true"></i><span class="admin-ai-btn__label">Boshqa rasm</span>
            </button>
            <button type="button" class="btn btn-outline-danger" data-ai-action="image-clear" @if(!$currentPhoto) hidden @endif>
              <i class="bi bi-trash me-2" aria-hidden="true"></i>Rasmni o‘chirish
            </button>
          </div>
          <div class="admin-ai-media__upload">
            <label class="form-label" for="edit-photo">Yoki kompyuterdan rasm yuklash</label>
            <input id="edit-photo" type="file" name="photo" class="form-control admin-file-input" accept="image/jpeg,image/png,image/webp" aria-describedby="edit-photo-help">
            <span class="admin-file-help" id="edit-photo-help">Mavjud rasm saqlanadi; almashtirish uchun yangisini tanlang.</span>
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-12 col-md-4"><label class="form-label" for="edit-schema-type">Schema type</label><select id="edit-schema-type" name="schema_type" class="form-select">@foreach(['BlogPosting','Article','MedicalWebPage'] as $type)<option @selected(old('schema_type', $article->schema_type)===$type)>{{ $type }}</option>@endforeach</select></div>
          <div class="col-12 col-md-4"><label class="form-label" for="edit-robots">Robots</label><select id="edit-robots" name="robots" class="form-select"><option value="index,follow" @selected($article->robots==='index,follow')>index,follow</option><option value="noindex,follow" @selected($article->robots==='noindex,follow')>noindex,follow</option></select></div>
          <div class="col-12 col-md-4"><label class="form-label" for="edit-og">OG image</label><input id="edit-og" name="og_image" class="form-control" value="{{ old('og_image', $article->og_image) }}"></div>
          <div class="col-12 col-md-6"><label class="form-label" for="edit-canonical">Canonical</label><input id="edit-canonical" type="url" name="canonical_url" class="form-control" value="{{ old('canonical_url', $article->canonical_url) }}"></div>
        </div>
      </section>

      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-2" aria-hidden="true"></i>Saqlash</button>
        <a class="btn btn-outline-secondary" href="{{ route('dashboard.articles.index') }}">Bekor qilish</a>
      </div>
    </form>
  </div>
@endsection

@push('scripts')
  <script src="{{ asset('js/article-ai.js') }}?v={{ filemtime(public_path('js/article-ai.js')) }}" defer></script>
@endpush
