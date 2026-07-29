@extends('admin.layouts.app')
@section('title', 'Yangi maqola')

@php $articleLocales = ['uz' => 'O‘zbek', 'ru' => 'Русский', 'en' => 'English']; @endphp

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
        <h1 class="h3 mb-1">Yangi maqola</h1>
        <p>Havolani qo‘ying va AI qadamlarini birma-bir bajaring. Majburiy maydonlar <span class="required-mark" aria-hidden="true">*</span> bilan.</p>
      </div>
      <a class="btn btn-outline-secondary" href="{{ route('dashboard.articles.index') }}"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Orqaga</a>
    </header>

    {{-- ===================== STEP 1: import from a link ===================== --}}
    <section class="admin-ai-panel mb-4" aria-labelledby="article-ai-title">
      <div class="admin-ai-panel__head">
        <span class="admin-ai-icon" aria-hidden="true"><i class="bi bi-stars"></i></span>
        <div>
          <h2 class="h5 mb-1" id="article-ai-title">AI yordamchisi — 4 mustaqil qadam</h2>
          <p class="small text-muted mb-0">1) Havoladan maqolani tayyorlash · 2) Boshqa tillarga tarjima · 3) SEO · 4) Rasm. Har biri alohida ishlaydi, natijani tekshirib saqlaysiz — hech narsa avtomatik e’lon qilinmaydi.</p>
        </div>
      </div>

      <div class="admin-ai-import mt-3">
        <label class="form-label fw-semibold" for="article-ai-url">Maqola havolasi</label>
        <div class="admin-ai-import__row">
          <input type="url" id="article-ai-url" data-ai-url class="form-control" placeholder="https://example.com/maqola-havolasi" autocomplete="off">
          <button type="button" class="btn btn-primary admin-ai-btn" data-ai-action="import">
            <i class="bi bi-magic me-2" aria-hidden="true"></i><span class="admin-ai-btn__label">AI orqali maqolani tayyorlash</span>
          </button>
        </div>
        <p class="admin-ai-hint mt-2"><i class="bi bi-shield-check me-1" aria-hidden="true"></i>Reklama, boshqa brend/dorixona nomlari va havolalar olib tashlanadi; maqola o‘z tilida professional tahrirlanadi va mos tilga joylanadi.</p>
        <span class="admin-ai-badge" data-ai-lang-badge hidden></span>
      </div>
    </section>

    <form method="POST" action="{{ route('dashboard.articles.store') }}" enctype="multipart/form-data" id="article-create-form" class="vstack gap-4" novalidate>
      @csrf
      <input type="hidden" name="photo_path" data-ai-photo-path>

      {{-- ===================== content + per-language SEO ===================== --}}
      <section class="admin-form-section" aria-labelledby="article-content-heading" data-editor-modal>
        <div class="admin-form-section__head"><span class="admin-form-step">1</span><div><h2 class="h5 mb-1" id="article-content-heading">Maqola matni — UZ / RU / EN</h2><p class="text-muted mb-0 small">Har bir til uchun sarlavha, matn va (pastda) SEO maydonlari.</p></div></div>

        <ul class="nav nav-tabs" role="tablist">
          @foreach($articleLocales as $locale => $label)
            <li class="nav-item"><button class="nav-link {{ $locale==='uz'?'active':'' }}" id="article-{{ $locale }}-tab" data-ai-tab="{{ $locale }}" data-bs-toggle="tab" data-bs-target="#article-{{ $locale }}-pane" type="button" role="tab" aria-controls="article-{{ $locale }}-pane" aria-selected="{{ $locale==='uz'?'true':'false' }}">{{ $label }}</button></li>
          @endforeach
        </ul>

        <div class="tab-content border border-top-0 rounded-bottom p-3">
          @foreach($articleLocales as $locale => $label)
            @php $titleField = 'title_'.$locale; @endphp
            <div class="tab-pane fade {{ $locale==='uz'?'show active':'' }}" id="article-{{ $locale }}-pane" role="tabpanel" aria-labelledby="article-{{ $locale }}-tab" tabindex="0"><div class="row g-3">
              <div class="col-12"><label class="form-label" for="article-{{ $titleField }}">Sarlavha ({{ strtoupper($locale) }}) <span class="required-mark" aria-hidden="true">*</span></label><input required class="form-control @error($titleField) is-invalid @enderror" id="article-{{ $titleField }}" name="{{ $titleField }}" value="{{ old($titleField) }}">@error($titleField)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
              <div class="col-12"><label class="form-label" for="article-description-{{ $locale }}">Maqola matni ({{ strtoupper($locale) }})</label><textarea class="form-control" id="article-description-{{ $locale }}" name="description_{{ $locale }}" rows="10" data-editor>{{ old('description_'.$locale) }}</textarea></div>

              <div class="col-12">
                <details class="admin-seo-details">
                  <summary><i class="bi bi-graph-up-arrow me-2" aria-hidden="true"></i>SEO ({{ strtoupper($locale) }}) — AI to‘ldiradi, tekshirib tuzatishingiz mumkin</summary>
                  <div class="row g-3 mt-1">
                    <div class="col-12 col-lg-6"><label class="form-label" for="article-seo-{{ $locale }}">SEO title <small class="text-muted">(≈50–60)</small></label><input class="form-control" id="article-seo-{{ $locale }}" name="seo_title_{{ $locale }}" value="{{ old('seo_title_'.$locale) }}"></div>
                    <div class="col-12 col-lg-6"><label class="form-label" for="article-slug-{{ $locale }}">URL slug</label><input class="form-control" id="article-slug-{{ $locale }}" name="slug_{{ $locale }}" value="{{ old('slug_'.$locale) }}"></div>
                    <div class="col-12"><label class="form-label" for="article-meta-{{ $locale }}">Meta description <small class="text-muted">(≈140–160)</small></label><textarea class="form-control" id="article-meta-{{ $locale }}" name="meta_description_{{ $locale }}" rows="2">{{ old('meta_description_'.$locale) }}</textarea></div>
                    <div class="col-12 col-lg-4"><label class="form-label" for="article-focus-{{ $locale }}">Asosiy kalit so‘z</label><input class="form-control" id="article-focus-{{ $locale }}" name="focus_keyword_{{ $locale }}" value="{{ old('focus_keyword_'.$locale) }}"></div>
                    <div class="col-12 col-lg-8"><label class="form-label" for="article-keywords-{{ $locale }}">Kalit so‘zlar <small class="text-muted">(vergul bilan)</small></label><input class="form-control" id="article-keywords-{{ $locale }}" name="keywords_{{ $locale }}_text" value="{{ old('keywords_'.$locale.'_text') }}"></div>
                    <div class="col-12 col-lg-6"><label class="form-label" for="article-ogtitle-{{ $locale }}">Open Graph title</label><input class="form-control" id="article-ogtitle-{{ $locale }}" name="og_title_{{ $locale }}" value="{{ old('og_title_'.$locale) }}"></div>
                    <div class="col-12 col-lg-6"><label class="form-label" for="article-ogdesc-{{ $locale }}">Open Graph description</label><input class="form-control" id="article-ogdesc-{{ $locale }}" name="og_description_{{ $locale }}" value="{{ old('og_description_'.$locale) }}"></div>
                    <div class="col-12 col-lg-8"><label class="form-label" for="article-schema-{{ $locale }}">Qisqa tavsif (schema)</label><textarea class="form-control" id="article-schema-{{ $locale }}" name="schema_description_{{ $locale }}" rows="2">{{ old('schema_description_'.$locale) }}</textarea></div>
                    <div class="col-12 col-lg-4"><label class="form-label" for="article-alt-{{ $locale }}">Rasm ALT matni</label><input class="form-control" id="article-alt-{{ $locale }}" name="image_alt_{{ $locale }}" value="{{ old('image_alt_'.$locale) }}"></div>
                  </div>
                </details>
              </div>

              <div class="col-12"><label class="form-label" for="article-references-{{ $locale }}">Manbalar — har qatorda bittadan</label><textarea class="form-control" id="article-references-{{ $locale }}" name="references_{{ $locale }}_text" rows="2">{{ old('references_'.$locale.'_text') }}</textarea></div>
              <div class="col-12 col-md-6"><label class="form-label" for="article-faq-q-{{ $locale }}">FAQ savol</label><input class="form-control" id="article-faq-q-{{ $locale }}" name="faq_questions_{{ $locale }}[]" value="{{ old('faq_questions_'.$locale.'.0') }}"></div>
              <div class="col-12 col-md-6"><label class="form-label" for="article-faq-a-{{ $locale }}">FAQ javob</label><textarea class="form-control" id="article-faq-a-{{ $locale }}" name="faq_answers_{{ $locale }}[]" rows="2">{{ old('faq_answers_'.$locale.'.0') }}</textarea></div>
            </div></div>
          @endforeach
        </div>

        {{-- Independent AI actions for translate + SEO --}}
        <div class="admin-ai-actions mt-3">
          <button type="button" class="btn btn-outline-primary admin-ai-btn" data-ai-action="translate">
            <i class="bi bi-translate me-2" aria-hidden="true"></i><span class="admin-ai-btn__label">AI orqali tarjima qilish</span>
          </button>
          <button type="button" class="btn btn-outline-primary admin-ai-btn" data-ai-action="seo">
            <i class="bi bi-graph-up-arrow me-2" aria-hidden="true"></i><span class="admin-ai-btn__label">AI orqali SEO yaratish</span>
          </button>
          <span class="admin-ai-hint"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Tarjima to‘ldirilgan tildan boshqa ikki tilga o‘giradi. Mavjud matn ustiga yozishdan oldin so‘raladi.</span>
        </div>
      </section>

      {{-- ===================== author / reviewer ===================== --}}
      <section class="admin-form-section" aria-labelledby="article-people-heading">
        <div class="admin-form-section__head"><span class="admin-form-step">2</span><div><h2 class="h5 mb-1" id="article-people-heading">Muallif va tekshiruv</h2><p class="text-muted mb-0 small">E-E-A-T uchun mualliflik ma’lumotlari (ixtiyoriy).</p></div></div>
        <div class="row g-3">
          <div class="col-12 col-md-6"><label class="form-label" for="article-author">Muallif</label><input class="form-control" id="article-author" name="author_name" value="{{ old('author_name') }}"></div>
          <div class="col-12 col-md-6"><label class="form-label" for="article-reviewer">Tekshiruvchi</label><input class="form-control" id="article-reviewer" name="reviewer_name" value="{{ old('reviewer_name') }}"></div>
          @foreach($articleLocales as $locale => $label)
            <div class="col-12 col-md-4"><label class="form-label" for="author-role-{{ $locale }}">Muallif lavozimi ({{ strtoupper($locale) }})</label><input class="form-control" id="author-role-{{ $locale }}" name="author_role_{{ $locale }}" value="{{ old('author_role_'.$locale) }}"></div>
            <div class="col-12 col-md-4"><label class="form-label" for="reviewer-role-{{ $locale }}">Tekshiruvchi lavozimi ({{ strtoupper($locale) }})</label><input class="form-control" id="reviewer-role-{{ $locale }}" name="reviewer_role_{{ $locale }}" value="{{ old('reviewer_role_'.$locale) }}"></div>
          @endforeach
          <div class="col-12 col-md-4"><label class="form-label" for="article-reviewed-at">Tekshirilgan sana</label><input class="form-control" id="article-reviewed-at" type="date" name="reviewed_at" value="{{ old('reviewed_at') }}"></div>
        </div>
      </section>

      {{-- ===================== STEP 4: image + publish ===================== --}}
      <section class="admin-form-section" aria-labelledby="article-media-heading">
        <div class="admin-form-section__head"><span class="admin-form-step">3</span><div><h2 class="h5 mb-1" id="article-media-heading">Asosiy rasm va nashr</h2><p class="text-muted mb-0 small">AI mavzuga mos rasm tanlaydi; yoqmasa boshqasini tanlang yoki qo‘lda yuklang.</p></div></div>

        <div class="admin-ai-media">
          <div class="admin-ai-media__preview" data-ai-image hidden>
            <img alt="Tanlangan rasm" data-ai-image-img>
            <span class="admin-ai-media__tag"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Bu rasm saqlaganda qo‘yiladi</span>
          </div>
          <div class="admin-ai-media__controls">
            <button type="button" class="btn btn-primary admin-ai-btn" data-ai-action="image">
              <i class="bi bi-image me-2" aria-hidden="true"></i><span class="admin-ai-btn__label">AI orqali rasm tanlash</span>
            </button>
            <button type="button" class="btn btn-outline-primary admin-ai-btn" data-ai-action="image-more" hidden>
              <i class="bi bi-arrow-repeat me-2" aria-hidden="true"></i><span class="admin-ai-btn__label">Boshqa rasm</span>
            </button>
            <button type="button" class="btn btn-outline-danger" data-ai-action="image-clear" hidden>
              <i class="bi bi-trash me-2" aria-hidden="true"></i>Rasmni o‘chirish
            </button>
          </div>
          <div class="admin-ai-media__upload">
            <label class="form-label" for="article-photo">Yoki kompyuterdan rasm yuklash</label>
            <input class="form-control admin-file-input" id="article-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" aria-describedby="article-photo-help">
            <span class="admin-file-help" id="article-photo-help">JPG / PNG / WEBP. Fayl tanlansa, AI rasmi o‘rniga u ishlatiladi.</span>
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-12 col-md-4"><label class="form-label" for="article-schema-type">Schema turi</label><select class="form-select" id="article-schema-type" name="schema_type">@foreach(['BlogPosting','Article','MedicalWebPage'] as $type)<option @selected(old('schema_type','BlogPosting')===$type)>{{ $type }}</option>@endforeach</select></div>
          <div class="col-12 col-md-4"><label class="form-label" for="article-robots">Robots</label><select class="form-select" id="article-robots" name="robots"><option value="index,follow">index,follow</option><option value="noindex,follow">noindex,follow</option></select></div>
          <div class="col-12 col-md-4"><label class="form-label" for="article-og">OG image URL/path</label><input class="form-control" id="article-og" name="og_image" value="{{ old('og_image') }}"></div>
          <div class="col-12 col-md-6"><label class="form-label" for="article-canonical">Canonical URL</label><input class="form-control" id="article-canonical" type="url" name="canonical_url" value="{{ old('canonical_url') }}"></div>
        </div>
      </section>

      <div class="d-flex flex-wrap justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('dashboard.articles.index') }}">Bekor qilish</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-2" aria-hidden="true"></i>Maqolani saqlash</button>
      </div>
    </form>
  </div>
@endsection

@push('scripts')
  <script src="{{ asset('js/article-ai.js') }}?v={{ filemtime(public_path('js/article-ai.js')) }}" defer></script>
@endpush
