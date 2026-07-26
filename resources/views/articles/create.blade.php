@extends('admin.layouts.app')
@section('title', 'Yangi maqola')

@php $articleLocales = ['uz' => 'O‘zbek', 'ru' => 'Русский', 'en' => 'English']; @endphp

@section('content')
  <header class="admin-page-header">
    <div>
      <span class="admin-eyebrow"><i class="bi bi-newspaper" aria-hidden="true"></i>Maqolalar</span>
      <h1 class="h3 mb-1">Yangi maqola</h1>
      <p>Majburiy maydonlar <span class="required-mark" aria-hidden="true">*</span> bilan belgilangan.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('dashboard.articles.index') }}"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Orqaga</a>
  </header>

  <section class="admin-ai-panel mb-4" aria-labelledby="article-ai-title">
    <div class="admin-ai-panel__head">
      <span class="admin-ai-icon" aria-hidden="true"><i class="bi bi-stars"></i></span>
      <div><h2 class="h5 mb-1" id="article-ai-title">AI bilan to‘ldirish</h2><p class="small text-muted mb-0">Maqola havolasini qo‘ying — AI mazmunni qayta yozadi (nusxa emas), 3 tilga tarjima qiladi va mavzuga mos rasm tanlaydi. Natijani tekshirib saqlang.</p></div>
    </div>
    <input type="url" id="article-ai-url" class="form-control mt-3" placeholder="https://example.com/maqola-havolasi">
    <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
      <button type="button" class="btn btn-primary" data-article-ai="full"><i class="bi bi-stars me-2" aria-hidden="true"></i>Maqolani to‘ldirish</button>
      <button type="button" class="btn btn-outline-primary" data-article-ai="translate"><i class="bi bi-translate me-2" aria-hidden="true"></i>UZ → RU/EN tarjima</button>
      <span class="admin-ai-status" id="article-ai-status" role="status" aria-live="polite"></span>
    </div>
    <div class="admin-ai-image mt-3" id="article-ai-image" hidden>
      <img alt="Avtomatik tanlangan rasm" id="article-ai-image-img">
      <span class="admin-ai-image__label"><i class="bi bi-image me-1" aria-hidden="true"></i>Avtomatik tanlangan rasm — saqlaganda qo‘yiladi</span>
    </div>
  </section>

  <form method="POST" action="{{ route('dashboard.articles.store') }}" enctype="multipart/form-data" id="article-create-form" class="vstack gap-4" novalidate>
    @csrf
    <input type="hidden" name="photo_path" id="article-photo-path">

    <section class="admin-form-section" aria-labelledby="article-content-heading" data-editor-modal>
      <div class="admin-form-section__head"><span class="admin-form-step">1</span><div><h2 class="h5 mb-1" id="article-content-heading">UZ / RU / EN kontent</h2><p class="text-muted mb-0 small">Har bir til uchun sarlavha, matn va SEO.</p></div></div>
      <ul class="nav nav-tabs" role="tablist">
        @foreach($articleLocales as $locale => $label)
          <li class="nav-item"><button class="nav-link {{ $locale==='uz'?'active':'' }}" id="article-{{ $locale }}-tab" data-bs-toggle="tab" data-bs-target="#article-{{ $locale }}-pane" type="button" role="tab" aria-controls="article-{{ $locale }}-pane" aria-selected="{{ $locale==='uz'?'true':'false' }}">{{ $label }}</button></li>
        @endforeach
      </ul>
      <div class="tab-content border border-top-0 rounded-bottom p-3">
        @foreach($articleLocales as $locale => $label)
          @php $titleField = 'title_'.$locale; @endphp
          <div class="tab-pane fade {{ $locale==='uz'?'show active':'' }}" id="article-{{ $locale }}-pane" role="tabpanel" aria-labelledby="article-{{ $locale }}-tab" tabindex="0"><div class="row g-3">
            <div class="col-12"><label class="form-label" for="article-{{ $titleField }}">Sarlavha ({{ strtoupper($locale) }}) <span class="required-mark" aria-hidden="true">*</span></label><input required class="form-control @error($titleField) is-invalid @enderror" id="article-{{ $titleField }}" name="{{ $titleField }}" value="{{ old($titleField) }}">@error($titleField)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label" for="article-description-{{ $locale }}">Maqola matni ({{ strtoupper($locale) }})</label><textarea class="form-control" id="article-description-{{ $locale }}" name="description_{{ $locale }}" rows="8" data-editor>{{ old('description_'.$locale) }}</textarea></div>
            <div class="col-12 col-lg-4"><label class="form-label" for="article-seo-{{ $locale }}">SEO title</label><input class="form-control" id="article-seo-{{ $locale }}" name="seo_title_{{ $locale }}" value="{{ old('seo_title_'.$locale) }}"></div>
            <div class="col-12 col-lg-4"><label class="form-label" for="article-meta-{{ $locale }}">Meta description</label><textarea class="form-control" id="article-meta-{{ $locale }}" name="meta_description_{{ $locale }}" rows="2">{{ old('meta_description_'.$locale) }}</textarea></div>
            <div class="col-12 col-lg-4"><label class="form-label" for="article-schema-{{ $locale }}">Schema description</label><textarea class="form-control" id="article-schema-{{ $locale }}" name="schema_description_{{ $locale }}" rows="2">{{ old('schema_description_'.$locale) }}</textarea></div>
            <div class="col-12"><label class="form-label" for="article-references-{{ $locale }}">Manbalar — har qatorda bittadan</label><textarea class="form-control" id="article-references-{{ $locale }}" name="references_{{ $locale }}_text" rows="3">{{ old('references_'.$locale.'_text') }}</textarea></div>
            <div class="col-12 col-md-6"><label class="form-label" for="article-faq-q-{{ $locale }}">FAQ savol</label><input class="form-control" id="article-faq-q-{{ $locale }}" name="faq_questions_{{ $locale }}[]" value="{{ old('faq_questions_'.$locale.'.0') }}"></div>
            <div class="col-12 col-md-6"><label class="form-label" for="article-faq-a-{{ $locale }}">FAQ javob</label><textarea class="form-control" id="article-faq-a-{{ $locale }}" name="faq_answers_{{ $locale }}[]" rows="2">{{ old('faq_answers_'.$locale.'.0') }}</textarea></div>
          </div></div>
        @endforeach
      </div>
    </section>

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

    <section class="admin-form-section" aria-labelledby="article-media-heading">
      <div class="admin-form-section__head"><span class="admin-form-step">3</span><div><h2 class="h5 mb-1" id="article-media-heading">Nashr, SEO va rasm</h2><p class="text-muted mb-0 small">Schema, robots, canonical va asosiy rasm.</p></div></div>
      <div class="row g-3">
        <div class="col-12 col-md-4"><label class="form-label" for="article-schema-type">Schema turi</label><select class="form-select" id="article-schema-type" name="schema_type">@foreach(['BlogPosting','Article','MedicalWebPage'] as $type)<option @selected(old('schema_type','BlogPosting')===$type)>{{ $type }}</option>@endforeach</select></div>
        <div class="col-12 col-md-4"><label class="form-label" for="article-robots">Robots</label><select class="form-select" id="article-robots" name="robots"><option value="index,follow">index,follow</option><option value="noindex,follow">noindex,follow</option></select></div>
        <div class="col-12 col-md-4"><label class="form-label" for="article-photo">Asosiy rasm</label><input class="form-control admin-file-input" id="article-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" aria-describedby="article-photo-help"><span class="admin-file-help" id="article-photo-help">Qo‘lda yuklash uchun. AI rasm tanlagan bo‘lsa, bo‘sh qoldiring.</span></div>
        <div class="col-12 col-md-6"><label class="form-label" for="article-canonical">Canonical URL</label><input class="form-control" id="article-canonical" type="url" name="canonical_url" value="{{ old('canonical_url') }}"></div>
        <div class="col-12 col-md-6"><label class="form-label" for="article-og">OG image URL/path</label><input class="form-control" id="article-og" name="og_image" value="{{ old('og_image') }}"></div>
      </div>
    </section>

    <div class="d-flex flex-wrap justify-content-end gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('dashboard.articles.index') }}">Bekor qilish</a>
      <button class="btn btn-primary" type="submit"><i class="bi bi-save me-2" aria-hidden="true"></i>Maqolani saqlash</button>
    </div>
  </form>
@endsection

@push('scripts')
<script>
  (function () {
    var form = document.getElementById('article-create-form');
    var urlInput = document.getElementById('article-ai-url');
    var status = document.getElementById('article-ai-status');
    var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-article-ai]'));
    var imgWrap = document.getElementById('article-ai-image');
    var imgEl = document.getElementById('article-ai-image-img');
    var photoPath = document.getElementById('article-photo-path');
    if (!form || !buttons.length) return;

    var endpoint = @json(route('dashboard.articles.ai-fill'));
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var LOCALES = ['uz', 'ru', 'en'];
    var KEYS = ['title', 'description', 'seo_title', 'meta_description', 'schema_description'];

    function setValue(name, value) {
      var el = form.querySelector('[name="' + name + '"]');
      if (!el) return;
      value = (value == null) ? '' : String(value);
      if (el.hasAttribute('data-editor') && el._adminEditor) {
        try { el._adminEditor.setData(value); } catch (e) {}
      }
      el.value = value;
      el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function apply(data, image, mode) {
      var locales = (mode === 'translate') ? ['ru', 'en'] : LOCALES;
      locales.forEach(function (loc) {
        KEYS.forEach(function (k) { setValue(k + '_' + loc, data[k + '_' + loc]); });
      });
      if (image && image.url && image.path) {
        imgEl.src = image.url;
        photoPath.value = image.path;
        imgWrap.hidden = false;
      }
    }

    function collectFields() {
      var out = {};
      LOCALES.forEach(function (loc) {
        KEYS.forEach(function (k) {
          var el = form.querySelector('[name="' + k + '_' + loc + '"]');
          var val = '';
          if (el) { val = (el._adminEditor ? el._adminEditor.getData() : el.value) || ''; }
          if (val.trim() !== '') out[k + '_' + loc] = val;
        });
      });
      return out;
    }

    function setBusy(busy, msg, isError) {
      buttons.forEach(function (b) { b.disabled = busy; });
      status.textContent = msg || '';
      status.className = 'admin-ai-status' + (busy ? ' is-busy' : '') + (isError ? ' is-error' : '');
    }

    function run(mode) {
      if (mode === 'full' && (!urlInput.value || urlInput.value.trim() === '')) {
        urlInput.focus();
        setBusy(false, 'Maqola havolasini kiriting.', true);
        return;
      }
      setBusy(true, 'AI ishlayapti… (bir necha soniya)');
      var body = { mode: mode, url: urlInput.value, fields: collectFields() };

      fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: JSON.stringify(body)
      }).then(function (r) {
        return r.json().then(function (json) { return { ok: r.ok, json: json }; });
      }).then(function (res) {
        if (!res.ok || !res.json || res.json.ok !== true) {
          throw new Error((res.json && res.json.error) || 'Xatolik yuz berdi.');
        }
        apply(res.json.data, res.json.image, mode);
        var imgMsg = res.json.image ? ' Rasm ham tanlandi.' : '';
        setBusy(false, '✓ Tayyor — tekshiring.' + imgMsg);
      }).catch(function (e) {
        setBusy(false, e.message || 'Xatolik yuz berdi.', true);
      });
    }

    buttons.forEach(function (b) {
      b.addEventListener('click', function () { run(b.getAttribute('data-article-ai')); });
    });
  })();
</script>
@endpush
