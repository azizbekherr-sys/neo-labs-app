@extends('admin.layouts.app')
@section('title', 'Yangi mahsulot')

@section('content')
  <header class="admin-page-header">
    <div>
      <span class="admin-eyebrow"><i class="bi bi-box-seam" aria-hidden="true"></i>Mahsulotlar</span>
      <h1 class="h3 mb-1">Yangi mahsulot</h1>
      <p>Avval asosiy UZ ma’lumotlarini kiriting. Tarjima va SEO sozlamalari ixtiyoriy.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('dashboard.products.index') }}"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Orqaga</a>
  </header>

  <section class="admin-ai-panel mb-4" aria-labelledby="ai-fill-title">
    <div class="admin-ai-panel__head">
      <span class="admin-ai-icon" aria-hidden="true"><i class="bi bi-stars"></i></span>
      <div>
        <h2 class="h5 mb-1" id="ai-fill-title">AI bilan avtomatik to‘ldirish</h2>
        <p class="small text-muted mb-0">O‘zbekcha umumiy ma’lumotni yozing — AI barcha maydonlarni to‘ldiradi, RU va EN ga tarjima qiladi hamda SEO ni yaratadi. Natijani saqlashdan oldin tekshiring.</p>
      </div>
    </div>
    <textarea id="ai-brief" class="form-control mt-3" rows="4" placeholder="Masalan: NEO Vitamin C 500 mg, 30 ta tabletka. Immunitetni qo‘llab-quvvatlaydi, teri salomatligi uchun foydali. Tarkibida askorbin kislotasi va rux. Kuniga 1 tabletkadan."></textarea>
    <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
      <button type="button" class="btn btn-primary" data-ai-action="full"><i class="bi bi-stars me-2" aria-hidden="true"></i>Hammasini to‘ldirish</button>
      <button type="button" class="btn btn-outline-primary" data-ai-action="translate"><i class="bi bi-translate me-2" aria-hidden="true"></i>UZ → RU/EN tarjima</button>
      <button type="button" class="btn btn-outline-primary" data-ai-action="seo"><i class="bi bi-search me-2" aria-hidden="true"></i>SEO ni to‘ldirish</button>
      <span class="admin-ai-status" id="ai-status" role="status" aria-live="polite"></span>
    </div>
  </section>

  <form method="POST" action="{{ route('dashboard.products.store') }}" enctype="multipart/form-data" id="product-create-form">
    @csrf
    @include('admin.products.partials.form-fields', [
      'product' => null,
      'formPrefix' => 'create-product',
    ])
    <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
      <a class="btn btn-outline-secondary" href="{{ route('dashboard.products.index') }}">Bekor qilish</a>
      <button class="btn btn-primary" type="submit"><i class="bi bi-save me-2" aria-hidden="true"></i>Mahsulotni saqlash</button>
    </div>
  </form>
@endsection

@push('scripts')
<script>
  (function () {
    var form = document.getElementById('product-create-form');
    var brief = document.getElementById('ai-brief');
    var status = document.getElementById('ai-status');
    var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-ai-action]'));
    if (!form || !buttons.length) return;

    var endpoint = @json(route('dashboard.products.ai-fill'));
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var LOCALES = ['uz', 'ru', 'en'];
    // AI key -> form field name
    var MAP = {
      name: 'name_', form: 'form_', packaging: 'packaging_count_', short: 'short_description_',
      composition: 'composition_', application: 'application_', warnings: 'warnings_',
      seo_title: 'seo_title_', meta_description: 'meta_description_'
    };

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

    function setBenefits(locale, arr) {
      var inputs = form.querySelectorAll('[name="benefits_' + locale + '[]"]');
      arr = Array.isArray(arr) ? arr : [];
      inputs.forEach(function (el, i) {
        el.value = arr[i] || '';
        el.dispatchEvent(new Event('input', { bubbles: true }));
      });
    }

    function openDisclosures() {
      form.querySelectorAll('details.admin-disclosure').forEach(function (d) { d.open = true; });
    }
    function enableSeoOverride() {
      var sw = form.querySelector('[data-seo-override]');
      if (sw && !sw.checked) { sw.checked = true; sw.dispatchEvent(new Event('change', { bubbles: true })); }
    }

    function applyLocale(data, locale, seoOnly) {
      if (!seoOnly) {
        Object.keys(MAP).forEach(function (k) {
          if (k === 'seo_title' || k === 'meta_description') return;
          setValue(MAP[k] + locale, data[k + '_' + locale]);
        });
        setBenefits(locale, data['benefits_' + locale]);
      }
      setValue('seo_title_' + locale, data['seo_title_' + locale]);
      setValue('meta_description_' + locale, data['meta_description_' + locale]);
    }

    function apply(data, mode) {
      if (mode === 'seo') {
        LOCALES.forEach(function (l) { applyLocale(data, l, true); });
        enableSeoOverride();
        return;
      }
      var locales = (mode === 'translate') ? ['ru', 'en'] : LOCALES;
      locales.forEach(function (l) { applyLocale(data, l, false); });
      if (mode === 'full') {
        setValue('description_uz', data.description_uz);
      }
      openDisclosures();
      enableSeoOverride();
    }

    function collectFields() {
      var out = {};
      var keys = ['name', 'form', 'packaging_count', 'short_description', 'composition', 'application', 'warnings', 'description'];
      LOCALES.forEach(function (l) {
        keys.forEach(function (k) {
          var el = form.querySelector('[name="' + k + '_' + l + '"]');
          if (el && el.value.trim() !== '') {
            // normalize packaging_count->packaging, short_description->short for the API
            var apiKey = k.replace('packaging_count', 'packaging').replace('short_description', 'short') + '_' + l;
            out[apiKey] = el.value;
          }
        });
        var benefits = Array.prototype.map.call(
          form.querySelectorAll('[name="benefits_' + l + '[]"]'), function (el) { return el.value; }
        ).filter(function (v) { return v.trim() !== ''; });
        if (benefits.length) out['benefits_' + l] = benefits;
      });
      return out;
    }

    function setBusy(busy, msg) {
      buttons.forEach(function (b) { b.disabled = busy; });
      status.textContent = msg || '';
      status.className = 'admin-ai-status' + (busy ? ' is-busy' : '');
    }

    function run(mode) {
      if (mode === 'full' && brief.value.trim() === '') {
        brief.focus();
        setBusy(false, 'Avval umumiy ma’lumotni yozing.');
        status.classList.add('is-error');
        return;
      }
      setBusy(true, 'AI ishlayapti…');
      var body = { mode: mode, brief: brief.value, fields: collectFields() };

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
        apply(res.json.data, mode);
        setBusy(false, '✓ Tayyor — natijani tekshiring.');
      }).catch(function (e) {
        setBusy(false, e.message || 'Xatolik yuz berdi.');
        status.classList.add('is-error');
      });
    }

    buttons.forEach(function (b) {
      b.addEventListener('click', function () { run(b.getAttribute('data-ai-action')); });
    });
  })();
</script>
@endpush
