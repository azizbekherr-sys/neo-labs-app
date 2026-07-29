/**
 * NEO-LABS admin — stepwise AI article toolkit.
 *
 * Four INDEPENDENT actions, each with its own request + button-local loading
 * state (the rest of the page stays usable):
 *   1. import     — read a link, professionally edit it in its own language
 *   2. translate  — translate the filled language into the other two
 *   3. seo        — generate a full SEO package per filled language
 *   4. image      — pick a topic image (Pexels); "Boshqa rasm" gives a new one
 *
 * Nothing auto-saves; results only populate the form for the admin to review.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-article-ai-root]');
  if (!root) return;
  var form = root.querySelector('form');
  if (!form) return;

  var LOCALES = ['uz', 'ru', 'en'];
  var LABELS = { uz: 'O‘zbek', ru: 'Rus', en: 'Ingliz' };
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var endpoints = {
    import: root.getAttribute('data-endpoint-import'),
    translate: root.getAttribute('data-endpoint-translate'),
    seo: root.getAttribute('data-endpoint-seo'),
    image: root.getAttribute('data-endpoint-image')
  };

  var urlInput = root.querySelector('[data-ai-url]');
  var langBadge = root.querySelector('[data-ai-lang-badge]');
  var photoPath = root.querySelector('[data-ai-photo-path]');
  var imageWrap = root.querySelector('[data-ai-image]');
  var imageEl = root.querySelector('[data-ai-image-img]');
  var fileInput = form.querySelector('input[type="file"][name="photo"]');

  var imageQueryCache = '';
  var imageVariant = 0;

  // ------------------------------------------------------------- field helpers
  function field(name) { return form.querySelector('[name="' + name + '"]'); }

  function getVal(name) {
    var el = field(name);
    if (!el) return '';
    if (el._adminEditor) { try { return el._adminEditor.getData() || ''; } catch (e) {} }
    return el.value || '';
  }

  function setVal(name, value) {
    var el = field(name);
    if (!el) return;
    value = (value == null) ? '' : String(value);
    if (el._adminEditor) { try { el._adminEditor.setData(value); } catch (e) {} }
    el.value = value;
    el.dispatchEvent(new Event('input', { bubbles: true }));
  }

  function has(name) { return getVal(name).trim() !== ''; }

  function activeLocale() {
    var el = root.querySelector('[data-ai-tab].active');
    return el ? el.getAttribute('data-ai-tab') : null;
  }

  /** Locale to use as the translation/SEO/image source. */
  function sourceLocale() {
    var active = activeLocale();
    if (active && has('title_' + active)) return active;
    for (var i = 0; i < LOCALES.length; i++) {
      if (has('title_' + LOCALES[i]) || has('description_' + LOCALES[i])) return LOCALES[i];
    }
    return active;
  }

  function switchTab(loc) {
    var btn = root.querySelector('[data-ai-tab="' + loc + '"]');
    if (btn && window.bootstrap && bootstrap.Tab) {
      try { bootstrap.Tab.getOrCreateInstance(btn).show(); } catch (e) {}
    }
  }

  // --------------------------------------------------------------------- toast
  function toastStack() {
    var s = document.querySelector('.admin-toast-stack');
    if (!s) {
      s = document.createElement('div');
      s.className = 'admin-toast-stack';
      s.setAttribute('aria-live', 'polite');
      document.body.appendChild(s);
    }
    return s;
  }

  function toast(message, type) {
    var el = document.createElement('div');
    el.className = 'admin-toast admin-toast--' + (type || 'info');
    var icon = type === 'success' ? 'bi-check-circle-fill' : (type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill');
    el.innerHTML = '<i class="bi ' + icon + '" aria-hidden="true"></i><span></span><button type="button" aria-label="Yopish">&times;</button>';
    el.querySelector('span').textContent = message;
    el.querySelector('button').addEventListener('click', function () { remove(); });
    toastStack().appendChild(el);
    var timer = window.setTimeout(remove, type === 'error' ? 8000 : 5000);
    function remove() {
      window.clearTimeout(timer);
      el.classList.add('is-leaving');
      window.setTimeout(function () { el.remove(); }, 250);
    }
  }

  // ------------------------------------------------------------- button loading
  function setBusy(btn, busy, text) {
    if (!btn) return;
    var label = btn.querySelector('.admin-ai-btn__label');
    if (busy) {
      btn.disabled = true;
      btn.classList.add('is-loading');
      if (label) {
        if (btn._label == null) btn._label = label.textContent;
        if (text) label.textContent = text;
      }
    } else {
      btn.disabled = false;
      btn.classList.remove('is-loading');
      if (label && btn._label != null) { label.textContent = btn._label; btn._label = null; }
    }
  }

  // ----------------------------------------------------------------- transport
  function post(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
      body: JSON.stringify(body)
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (json) {
        if (!r.ok || !json || json.ok !== true) {
          throw new Error((json && json.error) || 'Server bilan bog‘lanishda xatolik (' + r.status + ').');
        }
        return json;
      });
    });
  }

  // -------------------------------------------------------------- image helpers
  function showImage(url, path) {
    if (imageEl) imageEl.src = url;
    if (imageWrap) imageWrap.hidden = false;
    if (photoPath) photoPath.value = path || '';
    toggle('[data-ai-action="image-more"]', true);
    toggle('[data-ai-action="image-clear"]', true);
  }

  function clearImage() {
    if (imageWrap) imageWrap.hidden = true;
    if (imageEl) imageEl.src = '';
    if (photoPath) photoPath.value = '';
    imageQueryCache = '';
    imageVariant = 0;
    toggle('[data-ai-action="image-more"]', false);
    toggle('[data-ai-action="image-clear"]', false);
  }

  function toggle(selector, show) {
    var el = root.querySelector(selector);
    if (el) el.hidden = !show;
  }

  // ----------------------------------------------------------------- actions
  function doImport(btn) {
    var url = urlInput ? urlInput.value.trim() : '';
    if (!url) { if (urlInput) urlInput.focus(); toast('Maqola havolasini kiriting.', 'error'); return; }
    setBusy(btn, true, 'Havola o‘qilmoqda va tahrirlanmoqda…');
    post(endpoints.import, { url: url }).then(function (res) {
      var loc = res.lang;
      if (has('title_' + loc) || has('description_' + loc)) {
        if (!window.confirm('“' + LABELS[loc] + '” tilida allaqachon matn bor. AI natijasi bilan almashtirilsinmi?')) return;
      }
      setVal('title_' + loc, res.title);
      setVal('description_' + loc, res.body);
      switchTab(loc);
      if (langBadge) {
        langBadge.hidden = false;
        langBadge.innerHTML = '<i class="bi bi-translate me-1"></i>Aniqlangan til: ' + LABELS[loc];
      }
      toast('Maqola “' + LABELS[loc] + '” tilida tayyorlandi. Endi tarjima va SEO qadamlarini bajaring.', 'success');
    }).catch(function (e) {
      toast(e.message, 'error');
    }).then(function () { setBusy(btn, false); });
  }

  function doTranslate(btn) {
    var src = sourceLocale();
    if (!src || !has('title_' + src)) { toast('Avval biror tilda sarlavha va matnni to‘ldiring.', 'error'); return; }
    var targets = LOCALES.filter(function (l) { return l !== src; });
    var occupied = targets.filter(function (l) { return has('title_' + l) || has('description_' + l); });
    if (occupied.length) {
      var names = occupied.map(function (l) { return LABELS[l]; }).join(', ');
      if (!window.confirm(names + ' tilida matn bor. Tarjima ularning ustiga yozilsinmi?')) return;
    }
    setBusy(btn, true, 'Tarjima tayyorlanmoqda…');
    post(endpoints.translate, { source_lang: src, title: getVal('title_' + src), body: getVal('description_' + src) }).then(function (res) {
      var tr = res.translations || {};
      targets.forEach(function (l) {
        if (tr[l]) { setVal('title_' + l, tr[l].title); setVal('description_' + l, tr[l].body); }
      });
      toast('Tarjima tayyor: ' + targets.map(function (l) { return LABELS[l]; }).join(' va ') + '.', 'success');
    }).catch(function (e) {
      toast(e.message, 'error');
    }).then(function () { setBusy(btn, false); });
  }

  function doSeo(btn) {
    var fields = {};
    LOCALES.forEach(function (l) {
      var t = getVal('title_' + l), b = getVal('description_' + l);
      if (t.trim() || b.trim()) fields[l] = { title: t, body: b };
    });
    var locs = Object.keys(fields);
    if (!locs.length) { toast('Avval maqola matnini to‘ldiring.', 'error'); return; }
    var occupied = locs.filter(function (l) { return has('seo_title_' + l); });
    if (occupied.length && !window.confirm('Mavjud SEO ma’lumotlari AI natijasi bilan yangilansinmi?')) return;
    setBusy(btn, true, 'SEO ma’lumotlari yaratilmoqda…');
    post(endpoints.seo, { fields: fields }).then(function (res) {
      var seo = res.seo || {};
      Object.keys(seo).forEach(function (l) {
        var s = seo[l] || {};
        setVal('seo_title_' + l, s.seo_title);
        setVal('meta_description_' + l, s.meta_description);
        setVal('slug_' + l, s.slug);
        setVal('focus_keyword_' + l, s.focus_keyword);
        setVal('keywords_' + l + '_text', (s.keywords || []).join(', '));
        setVal('og_title_' + l, s.og_title);
        setVal('og_description_' + l, s.og_description);
        setVal('schema_description_' + l, s.schema_description);
        setVal('image_alt_' + l, s.image_alt);
      });
      toast('SEO tayyor: ' + Object.keys(seo).map(function (l) { return LABELS[l]; }).join(', ') + '. Har bir til uchun tekshiring.', 'success');
    }).catch(function (e) {
      toast(e.message, 'error');
    }).then(function () { setBusy(btn, false); });
  }

  function doImage(btn, more) {
    if (more) { imageVariant += 1; } else { imageVariant = 0; imageQueryCache = ''; }
    var payload;
    if (imageQueryCache) {
      payload = { query: imageQueryCache, variant: imageVariant };
    } else {
      var src = sourceLocale() || 'uz';
      payload = { title: getVal('title_' + src), body: getVal('description_' + src), variant: imageVariant };
    }
    setBusy(btn, true, more ? 'Boshqa rasm tanlanmoqda…' : 'Maqolaga mos rasm tanlanmoqda…');
    post(endpoints.image, payload).then(function (res) {
      imageQueryCache = res.query || imageQueryCache;
      if (res.image) {
        showImage(res.image.url, res.image.path);
        if (fileInput) fileInput.value = '';
        toast(more ? 'Yangi rasm tanlandi.' : 'Rasm tanlandi. Yoqmasa “Boshqa rasm”ni bosing.', 'success');
      }
    }).catch(function (e) {
      toast(e.message, 'error');
    }).then(function () { setBusy(btn, false); });
  }

  // --------------------------------------------------------------------- wiring
  root.querySelectorAll('[data-ai-action]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var action = btn.getAttribute('data-ai-action');
      if (action === 'import') doImport(btn);
      else if (action === 'translate') doTranslate(btn);
      else if (action === 'seo') doSeo(btn);
      else if (action === 'image') doImage(btn, false);
      else if (action === 'image-more') doImage(btn, true);
      else if (action === 'image-clear') clearImage();
    });
  });

  if (urlInput) {
    urlInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        var btn = root.querySelector('[data-ai-action="import"]');
        if (btn && !btn.disabled) doImport(btn);
      }
    });
  }

  // A manually chosen file replaces any AI image.
  if (fileInput) {
    fileInput.addEventListener('change', function () {
      if (fileInput.files && fileInput.files.length) clearImage();
    });
  }
})();
