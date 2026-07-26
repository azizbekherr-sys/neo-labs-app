@extends('admin.layouts.app')
@section('title', 'Maqolani tahrirlash')

@php $articleLocales = ['uz' => 'O‘zbek', 'ru' => 'Русский', 'en' => 'English']; @endphp

@section('content')
  <header class="admin-page-header">
    <div>
      <span class="admin-eyebrow"><i class="bi bi-newspaper" aria-hidden="true"></i>Maqolalar</span>
      <h1 class="h3 mb-1">{{ $article->title_uz }}</h1>
      <p>Uch tildagi kontent, mualliflik va SEO ma’lumotlarini tahrirlang.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('dashboard.articles.index') }}"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Orqaga</a>
  </header>

  <form method="POST" action="{{ route('dashboard.articles.update', $article) }}" enctype="multipart/form-data" class="vstack gap-4">
    @csrf @method('PUT')

    <section class="admin-form-section" aria-labelledby="edit-content-heading">
      <div class="admin-form-section__head"><span class="admin-form-step">1</span><div><h2 class="h5 mb-1" id="edit-content-heading">UZ / RU / EN kontent</h2><p class="text-muted mb-0 small">Har bir til uchun sarlavha, matn va SEO maydonlari.</p></div></div>
      <ul class="nav nav-tabs" role="tablist">
        @foreach($articleLocales as $locale => $localeLabel)
          <li class="nav-item"><button class="nav-link {{ $locale==='uz'?'active':'' }}" id="edit-{{ $locale }}-tab" data-bs-toggle="tab" data-bs-target="#edit-{{ $locale }}-pane" type="button" role="tab" aria-controls="edit-{{ $locale }}-pane" aria-selected="{{ $locale==='uz'?'true':'false' }}">{{ $localeLabel }}</button></li>
        @endforeach
      </ul>
      <div class="tab-content border border-top-0 rounded-bottom p-3">
        @foreach($articleLocales as $locale => $localeLabel)
          <div class="tab-pane fade {{ $locale==='uz' ? 'show active' : '' }}" id="edit-{{ $locale }}-pane" role="tabpanel" aria-labelledby="edit-{{ $locale }}-tab" tabindex="0"><div class="row g-3">
            <div class="col-12"><label class="form-label" for="edit-title-{{ $locale }}">Sarlavha ({{ strtoupper($locale) }}) <span class="required-mark" aria-hidden="true">*</span></label><input required id="edit-title-{{ $locale }}" name="title_{{ $locale }}" class="form-control" value="{{ old('title_'.$locale, $article->{'title_'.$locale}) }}"></div>
            <div class="col-12"><label class="form-label" for="edit-desc-{{ $locale }}">Maqola matni ({{ strtoupper($locale) }})</label><textarea id="edit-desc-{{ $locale }}" name="description_{{ $locale }}" rows="8" class="form-control editor">{{ old('description_'.$locale, $article->{'description_'.$locale}) }}</textarea></div>
            <div class="col-12 col-lg-4"><label class="form-label" for="edit-seo-{{ $locale }}">SEO title</label><input id="edit-seo-{{ $locale }}" name="seo_title_{{ $locale }}" class="form-control" value="{{ old('seo_title_'.$locale, $article->{'seo_title_'.$locale}) }}"></div>
            <div class="col-12 col-lg-4"><label class="form-label" for="edit-meta-{{ $locale }}">Meta description</label><textarea id="edit-meta-{{ $locale }}" name="meta_description_{{ $locale }}" class="form-control" rows="2">{{ old('meta_description_'.$locale, $article->{'meta_description_'.$locale}) }}</textarea></div>
            <div class="col-12 col-lg-4"><label class="form-label" for="edit-schema-{{ $locale }}">Schema description</label><textarea id="edit-schema-{{ $locale }}" name="schema_description_{{ $locale }}" class="form-control" rows="2">{{ old('schema_description_'.$locale, $article->{'schema_description_'.$locale}) }}</textarea></div>
            <div class="col-12"><label class="form-label" for="edit-refs-{{ $locale }}">Manbalar — har qatorda bittadan</label><textarea id="edit-refs-{{ $locale }}" name="references_{{ $locale }}_text" rows="3" class="form-control">{{ old('references_'.$locale.'_text', implode("\n", $article->{'references_'.$locale} ?: [])) }}</textarea></div>
            @php $faqs = $article->{'faqs_'.$locale} ?: [['question' => '', 'answer' => '']]; @endphp
            @foreach($faqs as $faq)
              <div class="col-12 col-md-6"><label class="form-label" for="edit-faq-q-{{ $locale }}-{{ $loop->index }}">FAQ savol</label><input id="edit-faq-q-{{ $locale }}-{{ $loop->index }}" name="faq_questions_{{ $locale }}[]" class="form-control" value="{{ $faq['question'] ?? '' }}"></div>
              <div class="col-12 col-md-6"><label class="form-label" for="edit-faq-a-{{ $locale }}-{{ $loop->index }}">FAQ javob</label><textarea id="edit-faq-a-{{ $locale }}-{{ $loop->index }}" name="faq_answers_{{ $locale }}[]" class="form-control" rows="2">{{ $faq['answer'] ?? '' }}</textarea></div>
            @endforeach
          </div></div>
        @endforeach
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
      <div class="admin-form-section__head"><span class="admin-form-step">3</span><div><h2 class="h5 mb-1" id="edit-seo-heading">Nashr, SEO va rasm</h2><p class="text-muted mb-0 small">Schema, robots, canonical va asosiy rasm.</p></div></div>
      <div class="row g-3">
        <div class="col-12 col-md-4"><label class="form-label" for="edit-schema-type">Schema type</label><select id="edit-schema-type" name="schema_type" class="form-select">@foreach(['BlogPosting','Article','MedicalWebPage'] as $type)<option @selected(old('schema_type', $article->schema_type)===$type)>{{ $type }}</option>@endforeach</select></div>
        <div class="col-12 col-md-4"><label class="form-label" for="edit-robots">Robots</label><select id="edit-robots" name="robots" class="form-select"><option value="index,follow" @selected($article->robots==='index,follow')>index,follow</option><option value="noindex,follow" @selected($article->robots==='noindex,follow')>noindex,follow</option></select></div>
        <div class="col-12 col-md-4"><label class="form-label" for="edit-photo">Yangi rasm</label><input id="edit-photo" type="file" name="photo" class="form-control admin-file-input" accept="image/*" aria-describedby="edit-photo-help"><span class="admin-file-help" id="edit-photo-help">Mavjud rasm saqlanadi; almashtirish uchun yangisini tanlang.</span></div>
        <div class="col-12 col-md-6"><label class="form-label" for="edit-canonical">Canonical</label><input id="edit-canonical" type="url" name="canonical_url" class="form-control" value="{{ old('canonical_url', $article->canonical_url) }}"></div>
        <div class="col-12 col-md-6"><label class="form-label" for="edit-og">OG image</label><input id="edit-og" name="og_image" class="form-control" value="{{ old('og_image', $article->og_image) }}"></div>
      </div>
    </section>

    <div class="d-flex flex-wrap gap-2">
      <button class="btn btn-primary" type="submit"><i class="bi bi-save me-2" aria-hidden="true"></i>Saqlash</button>
      <a class="btn btn-outline-secondary" href="{{ route('dashboard.articles.index') }}">Bekor qilish</a>
    </div>
  </form>
@endsection

@push('scripts')
<script>
  (function () {
    var loading;
    function load() {
      if (window.ClassicEditor) return Promise.resolve(window.ClassicEditor);
      if (loading) return loading;
      loading = new Promise(function (resolve, reject) {
        var s = document.createElement('script');
        s.src = 'https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js';
        s.onload = function () { resolve(window.ClassicEditor); };
        s.onerror = reject;
        document.head.appendChild(s);
      });
      return loading;
    }
    function init(pane) {
      var el = pane && pane.querySelector('.editor');
      if (!el || el.dataset.editorReady) return;
      el.dataset.editorReady = 'loading';
      load().then(function (Editor) {
        return Editor.create(el, { toolbar: ['bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo'] });
      }).then(function () { el.dataset.editorReady = 'true'; }).catch(function () { delete el.dataset.editorReady; });
    }
    init(document.querySelector('.tab-pane.active'));
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tab) {
      tab.addEventListener('shown.bs.tab', function (e) { init(document.querySelector(e.target.dataset.bsTarget)); });
    });
  })();
</script>
@endpush
