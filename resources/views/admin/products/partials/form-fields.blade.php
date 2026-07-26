@php
  $editing = $product !== null;
  $value = fn (string $field, $default = null) => old($field, $editing ? $product->{$field} : $default);
  $salesMode = old('sales_mode', $editing ? $product->effective_sales_mode : 'informational');
  $selectedRelated = collect(old('related_products', $editing ? $product->relatedProducts->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->all();
  $seoOverride = (bool) old('seo_override', $editing ? $product->seo_override : false);
  $locales = ['uz' => 'O‘zbek', 'ru' => 'Русский', 'en' => 'English'];
@endphp

<section class="admin-form-section" aria-labelledby="{{ $formPrefix }}-basic-title">
  <div class="admin-form-section__head">
    <span class="admin-form-step" aria-hidden="true">1</span>
    <div><h3 class="h5 mb-1" id="{{ $formPrefix }}-basic-title">Asosiy ma’lumotlar</h3><p class="small text-muted mb-0">Public sahifa uchun zarur bo‘lgan UZ kontent.</p></div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-lg-7">
      <label class="form-label" for="{{ $formPrefix }}-name-uz">Mahsulot nomi <span class="required-mark" aria-hidden="true">*</span></label>
      <input class="form-control @error('name_uz') is-invalid @enderror" id="{{ $formPrefix }}-name-uz" name="name_uz" value="{{ $value('name_uz') }}" maxlength="255" required @error('name_uz') aria-invalid="true" aria-describedby="{{ $formPrefix }}-name-uz-error" @enderror>
      @error('name_uz')<div class="invalid-feedback" id="{{ $formPrefix }}-name-uz-error">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6 col-lg-5">
      <label class="form-label" for="{{ $formPrefix }}-category">Kategoriya</label>
      <select class="form-select @error('category_id') is-invalid @enderror" id="{{ $formPrefix }}-category" name="category_id">
        <option value="">Tanlanmagan</option>
        @foreach($productCategories as $category)
          <option value="{{ $category->id }}" {{ (string)$value('category_id') === (string)$category->id ? 'selected' : '' }}>{{ $category->name_uz }}</option>
        @endforeach
      </select>
      @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
      <label class="form-label" for="{{ $formPrefix }}-form-uz">Mahsulot shakli</label>
      <input class="form-control @error('form_uz') is-invalid @enderror" id="{{ $formPrefix }}-form-uz" name="form_uz" value="{{ $value('form_uz') }}" maxlength="255" placeholder="Masalan: sirop, kapsula">
      @error('form_uz')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
      <label class="form-label" for="{{ $formPrefix }}-packaging-uz">Qadoq miqdori</label>
      <input class="form-control @error('packaging_count_uz') is-invalid @enderror" id="{{ $formPrefix }}-packaging-uz" name="packaging_count_uz" value="{{ $value('packaging_count_uz') }}" maxlength="255" placeholder="Masalan: 100 ml yoki 30 kapsula">
      @error('packaging_count_uz')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
      <label class="form-label" for="{{ $formPrefix }}-short-uz">Qisqa tavsif</label>
      <textarea class="form-control @error('short_description_uz') is-invalid @enderror" id="{{ $formPrefix }}-short-uz" name="short_description_uz" rows="3" maxlength="220" data-character-count aria-describedby="{{ $formPrefix }}-short-help">{{ $value('short_description_uz') }}</textarea>
      <div class="form-text d-flex justify-content-between" id="{{ $formPrefix }}-short-help"><span>Hero uchun 160–220 belgigacha aniq mazmun.</span><span data-character-output>0/220</span></div>
      @error('short_description_uz')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
      <fieldset>
        <legend class="form-label">Asosiy foydalar <span class="text-muted fw-normal">(ko‘pi bilan 3 ta)</span></legend>
        <div class="row g-2">
          @php $uzBenefits = collect(old('benefits_uz', $editing ? ($product->benefits_uz ?: []) : [])); @endphp
          @for($index = 0; $index < 3; $index++)
            <div class="col-12 col-lg-4">
              <label class="visually-hidden" for="{{ $formPrefix }}-benefit-uz-{{ $index }}">Foyda {{ $index + 1 }}</label>
              <input class="form-control @error('benefits_uz.'.$index) is-invalid @enderror" id="{{ $formPrefix }}-benefit-uz-{{ $index }}" name="benefits_uz[]" value="{{ $uzBenefits->get($index) }}" maxlength="160" placeholder="{{ $index + 1 }}-foyda">
              @error('benefits_uz.'.$index)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          @endfor
        </div>
      </fieldset>
    </div>
    @foreach([
      ['composition_uz', 'Tarkibi', 4],
      ['application_uz', 'Qo‘llash usuli', 4],
      ['warnings_uz', 'Ogohlantirish va qarshi ko‘rsatmalar', 4],
    ] as [$field, $label, $rows])
      <div class="col-12">
        <label class="form-label" for="{{ $formPrefix }}-{{ str_replace('_', '-', $field) }}">{{ $label }}</label>
        <textarea class="form-control @error($field) is-invalid @enderror" id="{{ $formPrefix }}-{{ str_replace('_', '-', $field) }}" name="{{ $field }}" rows="{{ $rows }}">{{ $value($field) }}</textarea>
        @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
    @endforeach
    <div class="col-12">
      <label class="form-label" for="{{ $formPrefix }}-images">Asosiy rasm</label>
      <input class="form-control admin-file-input @error('images.*') is-invalid @enderror" id="{{ $formPrefix }}-images" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" {{ $editing ? 'multiple' : '' }} aria-describedby="{{ $formPrefix }}-images-help">
      <span class="admin-file-help" id="{{ $formPrefix }}-images-help">JPG, PNG yoki WebP; 6 MB gacha. Birinchi rasm asosiy hisoblanadi.</span>
      @error('images.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
      @if($editing && collect($product->images ?: [$product->image])->filter()->isNotEmpty())
        <div class="admin-current-media mt-2">
          @foreach(collect($product->images ?: [$product->image])->filter() as $image)
            <label class="admin-current-media__item">
              <img src="{{ media_url($image) }}" alt="{{ $product->name_uz }} rasmi">
              <span><input type="checkbox" name="remove_images[]" value="{{ $image }}"> Olib tashlash</span>
            </label>
          @endforeach
        </div>
      @endif
    </div>
  </div>
</section>

<section class="admin-form-section mt-4" aria-labelledby="{{ $formPrefix }}-sales-title">
  <div class="admin-form-section__head">
    <span class="admin-form-step" aria-hidden="true">2</span>
    <div><h3 class="h5 mb-1" id="{{ $formPrefix }}-sales-title">Sotuv va nashr</h3><p class="small text-muted mb-0">CTA faqat tanlangan rejimga mos ko‘rsatiladi.</p></div>
  </div>
  <div class="row g-3" data-sales-mode-group>
    <div class="col-12 col-md-6">
      <label class="form-label" for="{{ $formPrefix }}-sales-mode">Sotuv rejimi</label>
      <select class="form-select @error('sales_mode') is-invalid @enderror" id="{{ $formPrefix }}-sales-mode" name="sales_mode" data-sales-mode>
        <option value="informational" {{ $salesMode === 'informational' ? 'selected' : '' }}>Maslahat olish</option>
        <option value="external" {{ $salesMode === 'external' ? 'selected' : '' }}>Hamkor do‘konga yo‘naltirish</option>
        <option value="direct" {{ $salesMode === 'direct' ? 'selected' : '' }}>To‘g‘ridan-to‘g‘ri sotuv</option>
      </select>
      @error('sales_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
      <label class="form-label" for="{{ $formPrefix }}-status">Public holati <span class="required-mark" aria-hidden="true">*</span></label>
      <select class="form-select @error('status') is-invalid @enderror" id="{{ $formPrefix }}-status" name="status" required>
        <option value="draft" {{ $value('status', 'draft') === 'draft' ? 'selected' : '' }}>Qoralama</option>
        <option value="active" {{ $value('status') === 'active' ? 'selected' : '' }}>Faol</option>
        <option value="paused" {{ $value('status') === 'paused' ? 'selected' : '' }}>To‘xtatilgan</option>
      </select>
      @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12" data-sales-panel="external">
      <label class="form-label" for="{{ $formPrefix }}-external-url">Hamkor do‘kon URL’i</label>
      <input class="form-control @error('external_purchase_url') is-invalid @enderror" id="{{ $formPrefix }}-external-url" type="url" name="external_purchase_url" value="{{ $value('external_purchase_url') }}" placeholder="https://">
      @error('external_purchase_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12" data-sales-panel="direct">
      <div class="row g-3">
        <div class="col-12 col-md-4">
          <label class="form-label" for="{{ $formPrefix }}-price">Narx</label>
          <input class="form-control @error('price') is-invalid @enderror" id="{{ $formPrefix }}-price" type="number" min="0.01" step="0.01" name="price" value="{{ $value('price') }}">
          @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-md-4">
          <label class="form-label" for="{{ $formPrefix }}-currency">Valyuta</label>
          <select class="form-select @error('currency') is-invalid @enderror" id="{{ $formPrefix }}-currency" name="currency">
            @foreach(['UZS', 'USD', 'EUR'] as $currency)<option value="{{ $currency }}" {{ $value('currency', 'UZS') === $currency ? 'selected' : '' }}>{{ $currency }}</option>@endforeach
          </select>
          @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-md-4">
          <label class="form-label" for="{{ $formPrefix }}-stock-status">Mavjudlik</label>
          <select class="form-select @error('stock_status') is-invalid @enderror" id="{{ $formPrefix }}-stock-status" name="stock_status">
            <option value="in_stock" {{ $value('stock_status', 'in_stock') === 'in_stock' ? 'selected' : '' }}>Mavjud</option>
            <option value="out_of_stock" {{ $value('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Mavjud emas</option>
            <option value="preorder" {{ $value('stock_status') === 'preorder' ? 'selected' : '' }}>Oldindan buyurtma</option>
          </select>
          @error('stock_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div>
    </div>
  </div>
</section>

<details class="admin-disclosure mt-4" @if($errors->hasAny(['name_ru','name_en','short_description_ru','short_description_en'])) open @endif>
  <summary><span><strong>RU va EN tarjimalar</strong><small>Ixtiyoriy — bo‘sh qolsa mavjud fallback ishlaydi</small></span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
  <div class="admin-disclosure__body">
    <ul class="nav nav-tabs" role="tablist">
      @foreach(['ru' => 'Русский', 'en' => 'English'] as $locale => $label)
        <li class="nav-item" role="presentation"><button class="nav-link {{ $locale === 'ru' ? 'active' : '' }}" id="{{ $formPrefix }}-{{ $locale }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $formPrefix }}-{{ $locale }}-pane" type="button" role="tab" aria-controls="{{ $formPrefix }}-{{ $locale }}-pane" aria-selected="{{ $locale === 'ru' ? 'true' : 'false' }}">{{ $label }}</button></li>
      @endforeach
    </ul>
    <div class="tab-content border border-top-0 rounded-bottom p-3">
      @foreach(['ru' => 'Русский', 'en' => 'English'] as $locale => $label)
        <div class="tab-pane fade {{ $locale === 'ru' ? 'show active' : '' }}" id="{{ $formPrefix }}-{{ $locale }}-pane" role="tabpanel" aria-labelledby="{{ $formPrefix }}-{{ $locale }}-tab" tabindex="0">
          <div class="row g-3">
            @foreach([
              ['name_'.$locale, 'Mahsulot nomi', 'input', 1],
              ['form_'.$locale, 'Mahsulot shakli', 'input', 1],
              ['packaging_count_'.$locale, 'Qadoq miqdori', 'input', 1],
              ['short_description_'.$locale, 'Qisqa tavsif', 'textarea', 3],
              ['composition_'.$locale, 'Tarkibi', 'textarea', 4],
              ['application_'.$locale, 'Qo‘llash usuli', 'textarea', 4],
              ['warnings_'.$locale, 'Ogohlantirishlar', 'textarea', 4],
            ] as [$field, $fieldLabel, $control, $rows])
              <div class="{{ in_array($field, ['form_'.$locale, 'packaging_count_'.$locale], true) ? 'col-12 col-md-6' : 'col-12' }}">
                <label class="form-label" for="{{ $formPrefix }}-{{ str_replace('_', '-', $field) }}">{{ $fieldLabel }} ({{ strtoupper($locale) }})</label>
                @if($control === 'input')
                  <input class="form-control @error($field) is-invalid @enderror" id="{{ $formPrefix }}-{{ str_replace('_', '-', $field) }}" name="{{ $field }}" value="{{ $value($field) }}" maxlength="255">
                @else
                  <textarea class="form-control @error($field) is-invalid @enderror" id="{{ $formPrefix }}-{{ str_replace('_', '-', $field) }}" name="{{ $field }}" rows="{{ $rows }}" @if(\Illuminate\Support\Str::startsWith($field, 'short_description')) maxlength="220" @endif>{{ $value($field) }}</textarea>
                @endif
                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            @endforeach
            <div class="col-12">
              <fieldset><legend class="form-label">Asosiy foydalar ({{ strtoupper($locale) }})</legend><div class="row g-2">
                @php $localeBenefits = collect(old('benefits_'.$locale, $editing ? ($product->{'benefits_'.$locale} ?: []) : [])); @endphp
                @for($index = 0; $index < 3; $index++)
                  <div class="col-12 col-lg-4"><label class="visually-hidden" for="{{ $formPrefix }}-benefit-{{ $locale }}-{{ $index }}">Foyda {{ $index + 1 }}</label><input class="form-control" id="{{ $formPrefix }}-benefit-{{ $locale }}-{{ $index }}" name="benefits_{{ $locale }}[]" value="{{ $localeBenefits->get($index) }}" maxlength="160"></div>
                @endfor
              </div></fieldset>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</details>

<details class="admin-disclosure mt-3" @if($errors->hasAny(['medical_review_status','sku','barcode','instruction_file','related_products','canonical_url'])) open @endif>
  <summary><span><strong>Qo‘shimcha sozlamalar</strong><small>Identifikatorlar, ishonch ma’lumotlari, FAQ, bog‘lanish va SEO</small></span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
  <div class="admin-disclosure__body">
    <div class="row g-3">
      @foreach([['sku','SKU'],['barcode','Shtrix-kod'],['manufacturer','Ishlab chiqaruvchi'],['country_of_origin','Davlat ISO kodi']] as [$field,$label])
        <div class="col-12 col-md-6"><label class="form-label" for="{{ $formPrefix }}-{{ $field }}">{{ $label }}</label><input class="form-control @error($field) is-invalid @enderror" id="{{ $formPrefix }}-{{ $field }}" name="{{ $field }}" value="{{ $value($field, $field === 'country_of_origin' ? 'UZ' : null) }}" @if($field === 'country_of_origin') maxlength="2" @endif>@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
      @endforeach
      @foreach([['storage_conditions_uz','Saqlash sharoiti'],['shelf_life_uz','Yaroqlilik muddati'],['registration_info_uz','Ro‘yxatdan o‘tish yoki sertifikat']] as [$field,$label])
        <div class="{{ $field === 'shelf_life_uz' ? 'col-12 col-md-6' : 'col-12' }}"><label class="form-label" for="{{ $formPrefix }}-{{ $field }}">{{ $label }}</label>@if($field === 'shelf_life_uz')<input class="form-control" id="{{ $formPrefix }}-{{ $field }}" name="{{ $field }}" value="{{ $value($field) }}">@else<textarea class="form-control" id="{{ $formPrefix }}-{{ $field }}" name="{{ $field }}" rows="3">{{ $value($field) }}</textarea>@endif</div>
      @endforeach
      <div class="col-12"><label class="form-label" for="{{ $formPrefix }}-description-uz">Mahsulot haqida to‘liq matn</label><textarea class="form-control" id="{{ $formPrefix }}-description-uz" name="description_uz" rows="5" data-editor>{{ $value('description_uz') }}</textarea></div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="{{ $formPrefix }}-instruction">PDF yo‘riqnoma</label>
        <input class="form-control admin-file-input" id="{{ $formPrefix }}-instruction" type="file" name="instruction_file" accept="application/pdf" aria-describedby="{{ $formPrefix }}-instruction-help">
        <span class="admin-file-help" id="{{ $formPrefix }}-instruction-help">PDF, 10 MB gacha.</span>
        @if($editing && $product->instruction_file)<a class="small d-inline-block mt-1" href="{{ media_url($product->instruction_file) }}" target="_blank" rel="noopener">Mavjud yo‘riqnomani ko‘rish</a>@endif
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="{{ $formPrefix }}-review-status">Tibbiy review holati</label>
        <select class="form-select @error('medical_review_status') is-invalid @enderror" id="{{ $formPrefix }}-review-status" name="medical_review_status" aria-describedby="{{ $formPrefix }}-review-help">
          <option value="not_required" {{ $value('medical_review_status', 'not_required') === 'not_required' ? 'selected' : '' }}>Talab qilinmaydi</option>
          <option value="pending" {{ $value('medical_review_status') === 'pending' ? 'selected' : '' }}>Tekshiruv kutilmoqda</option>
          <option value="approved" {{ $value('medical_review_status') === 'approved' ? 'selected' : '' }}>Tasdiqlangan</option>
        </select>
        <div class="form-text" id="{{ $formPrefix }}-review-help">“Davolaydi”, “oldini oladi”, “xavfsiz” kabi da’volar tasdiqsiz nashr qilinmaydi.</div>
        @error('medical_review_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      @if($editing)
        <div class="col-12">
          <label class="form-label" for="{{ $formPrefix }}-related">Bog‘langan mahsulotlar</label>
          <select class="form-select" id="{{ $formPrefix }}-related" name="related_products[]" multiple size="5" aria-describedby="{{ $formPrefix }}-related-help">
            @foreach($productOptions as $option)
              @continue($option->id === $product->id)
              <option value="{{ $option->id }}" {{ in_array((int)$option->id, $selectedRelated, true) ? 'selected' : '' }}>{{ $option->name_uz ?: $option->name }}</option>
            @endforeach
          </select>
          <div class="form-text" id="{{ $formPrefix }}-related-help">Bir nechta tanlash uchun Ctrl/Cmd tugmasini ushlab turing.</div>
        </div>
      @endif
      <div class="col-12">
        <h4 class="h6">FAQ (UZ)</h4>
        @php $uzFaqs = collect(old('faq_questions_uz') ? collect(old('faq_questions_uz'))->map(fn($q,$i)=>['question'=>$q,'answer'=>old('faq_answers_uz.'.$i)])->all() : ($editing ? ($product->faqs_uz ?: []) : [])); if($uzFaqs->isEmpty()) $uzFaqs = collect([['question'=>'','answer'=>'']]); @endphp
        @foreach($uzFaqs as $index => $faq)
          <div class="row g-2 mb-2"><div class="col-12 col-md-5"><label class="visually-hidden" for="{{ $formPrefix }}-faq-q-{{ $index }}">Savol</label><input class="form-control" id="{{ $formPrefix }}-faq-q-{{ $index }}" name="faq_questions_uz[]" value="{{ $faq['question'] ?? '' }}" placeholder="Savol"></div><div class="col-12 col-md-7"><label class="visually-hidden" for="{{ $formPrefix }}-faq-a-{{ $index }}">Javob</label><textarea class="form-control" id="{{ $formPrefix }}-faq-a-{{ $index }}" name="faq_answers_uz[]" rows="2" placeholder="Javob">{{ $faq['answer'] ?? '' }}</textarea></div></div>
        @endforeach
      </div>
      <div class="col-12 d-flex flex-wrap gap-4">
        <div class="form-check"><input type="hidden" name="is_featured" value="0"><input class="form-check-input" type="checkbox" id="{{ $formPrefix }}-featured" name="is_featured" value="1" {{ (bool)$value('is_featured') ? 'checked' : '' }}><label class="form-check-label" for="{{ $formPrefix }}-featured">Bosh sahifada ko‘rsatish</label></div>
        <div class="form-check"><input type="hidden" name="prescription" value="0"><input class="form-check-input" type="checkbox" id="{{ $formPrefix }}-prescription" name="prescription" value="1" {{ (bool)$value('prescription') ? 'checked' : '' }}><label class="form-check-label" for="{{ $formPrefix }}-prescription">Retsept talab qilinadi</label></div>
      </div>
    </div>

    <div class="admin-seo-override mt-4" data-seo-override-group>
      <div class="form-check form-switch">
        <input type="hidden" name="seo_override" value="0">
        <input class="form-check-input" type="checkbox" role="switch" id="{{ $formPrefix }}-seo-override" name="seo_override" value="1" data-seo-override {{ $seoOverride ? 'checked' : '' }}>
        <label class="form-check-label fw-semibold" for="{{ $formPrefix }}-seo-override">SEO sozlamalarini qo‘lda o‘zgartirish</label>
      </div>
      <p class="small text-muted mt-1">O‘chiq holatda title nomdan, description qisqa tavsifdan, OG image esa asosiy rasmdan avtomatik olinadi.</p>
      <div class="row g-3 mt-1" data-seo-panel>
        @foreach($locales as $locale => $localeLabel)
          <div class="col-12 col-lg-4"><label class="form-label" for="{{ $formPrefix }}-seo-title-{{ $locale }}">SEO title ({{ strtoupper($locale) }})</label><input class="form-control" id="{{ $formPrefix }}-seo-title-{{ $locale }}" name="seo_title_{{ $locale }}" value="{{ $value('seo_title_'.$locale) }}"></div>
          <div class="col-12 col-lg-4"><label class="form-label" for="{{ $formPrefix }}-meta-{{ $locale }}">Meta description ({{ strtoupper($locale) }})</label><textarea class="form-control" id="{{ $formPrefix }}-meta-{{ $locale }}" name="meta_description_{{ $locale }}" rows="3">{{ $value('meta_description_'.$locale) }}</textarea></div>
          <div class="col-12 col-lg-4"><label class="form-label" for="{{ $formPrefix }}-schema-{{ $locale }}">Schema description ({{ strtoupper($locale) }})</label><textarea class="form-control" id="{{ $formPrefix }}-schema-{{ $locale }}" name="schema_description_{{ $locale }}" rows="3">{{ $value('schema_description_'.$locale) }}</textarea></div>
        @endforeach
        <div class="col-12 col-lg-6"><label class="form-label" for="{{ $formPrefix }}-canonical">Canonical URL</label><input class="form-control" id="{{ $formPrefix }}-canonical" type="url" name="canonical_url" value="{{ $value('canonical_url') }}"></div>
        <div class="col-6 col-lg-3"><label class="form-label" for="{{ $formPrefix }}-robots">Robots</label><select class="form-select" id="{{ $formPrefix }}-robots" name="robots"><option value="index,follow" {{ $value('robots', 'index,follow') === 'index,follow' ? 'selected' : '' }}>index,follow</option><option value="noindex,follow" {{ $value('robots') === 'noindex,follow' ? 'selected' : '' }}>noindex,follow</option></select></div>
        <div class="col-6 col-lg-3"><label class="form-label" for="{{ $formPrefix }}-og">OG image</label><input class="form-control" id="{{ $formPrefix }}-og" name="og_image" value="{{ $value('og_image') }}"></div>
      </div>
    </div>
  </div>
</details>
