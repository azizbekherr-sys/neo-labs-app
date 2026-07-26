@php
  $mfgTitle = __('manufacturing.title');
  $mfgDescription = __('manufacturing.description');
  $locale = app()->getLocale();
  $mfgSchema = [
    '@type' => 'Service',
    '@id' => \App\Support\Seo::canonical() . '#service',
    'name' => $mfgTitle,
    'description' => $mfgDescription,
    'provider' => ['@id' => \App\Support\Seo::baseUrl() . '/#organization'],
    'areaServed' => ['@type' => 'Country', 'name' => __('site.manufacturing.country')],
    'serviceType' => __('site.manufacturing.service_type'),
  ];
  $partnerMeta = [
    'pr_69280b6e953d2_1764232046.PNG' => ['name' => 'LifePrime', 'width' => 360, 'height' => 360],
    'pr_69281ccb176a6_1764236491.png' => ['name' => 'Evert Pharma AG', 'width' => 360, 'height' => 231],
    'pr_69281cf134421_1764236529.jpg' => ['name' => 'Family Calm', 'width' => 360, 'height' => 360],
    'pr_693c2d6df0981_1765551469.jpg' => ['name' => 'Biofit', 'width' => 360, 'height' => 360],
  ];
@endphp

@push('head')
  <link
    rel="preload"
    as="image"
    href="{{ asset('img/responsive/neo-labs-contract-manufacturing-960.webp') }}"
    imagesrcset="{{ asset('img/responsive/neo-labs-contract-manufacturing-640.webp') }} 640w, {{ asset('img/responsive/neo-labs-contract-manufacturing-960.webp') }} 960w, {{ asset('img/responsive/neo-labs-contract-manufacturing-1280.webp') }} 1280w"
    imagesizes="(max-width: 767px) 92vw, 1200px"
    type="image/webp"
    fetchpriority="high"
  />
  <link rel="stylesheet" href="{{ asset('css/manufacturing.css') }}" />
@endpush

@push('scripts')
  <script src="{{ asset('js/manufacturing.js') }}" defer></script>
@endpush

<x-layouts.index :title="$mfgTitle" :description="$mfgDescription" :schema="$mfgSchema" image="/img/responsive/neo-labs-contract-manufacturing-1280.webp">
  <main id="main-content" class="mfg-page" tabindex="-1">
    <section class="mfg-hero" aria-labelledby="mfg-title">
      <div class="container">
        <div class="mfg-hero__shell">
          <picture class="mfg-hero__picture">
            <source
              type="image/avif"
              srcset="{{ asset('img/responsive/neo-labs-contract-manufacturing-640.avif') }} 640w, {{ asset('img/responsive/neo-labs-contract-manufacturing-960.avif') }} 960w, {{ asset('img/responsive/neo-labs-contract-manufacturing-1280.avif') }} 1280w"
              sizes="(max-width: 767px) 92vw, 1200px"
            />
            <source
              type="image/webp"
              srcset="{{ asset('img/responsive/neo-labs-contract-manufacturing-640.webp') }} 640w, {{ asset('img/responsive/neo-labs-contract-manufacturing-960.webp') }} 960w, {{ asset('img/responsive/neo-labs-contract-manufacturing-1280.webp') }} 1280w"
              sizes="(max-width: 767px) 92vw, 1200px"
            />
            <img
              src="{{ asset('img/responsive/neo-labs-contract-manufacturing-960.webp') }}"
              width="1280"
              height="853"
              alt="{{ __('manufacturing.hero.image_alt') }}"
              decoding="async"
              fetchpriority="high"
            />
          </picture>
          <div class="mfg-hero__overlay" aria-hidden="true"></div>
          <div class="mfg-hero__content">
            <p class="mfg-eyebrow">{{ __('manufacturing.hero.eyebrow') }}</p>
            <h1 id="mfg-title">{{ __('manufacturing.title') }}</h1>
            <p class="mfg-hero__value">{{ __('manufacturing.hero.value') }}</p>
            <div class="mfg-hero__actions">
              <a class="mfg-button" data-analytics="contract_request" href="#manufacturing-request">
                {{ __('manufacturing.hero.primary') }}
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 14 0M13 6l6 6-6 6"/></svg>
              </a>
              <a class="mfg-button mfg-button--light" data-analytics="phone_click" href="tel:+998991018839">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92Z"/></svg>
                {{ __('manufacturing.hero.secondary') }}
              </a>
            </div>
            <ul class="mfg-format-list" aria-label="{{ __('manufacturing.products.title') }}">
              @foreach(__('manufacturing.hero.formats') as $format)
                <li>{{ $format }}</li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>
    </section>

    <section class="mfg-section" aria-labelledby="mfg-overview-title">
      <div class="container mfg-overview">
        <div>
          <p class="mfg-eyebrow">{{ __('manufacturing.overview.eyebrow') }}</p>
          <h2 class="mfg-heading" id="mfg-overview-title">{{ __('manufacturing.overview.title') }}</h2>
          <p class="mfg-lead">{{ __('manufacturing.overview.text') }}</p>
        </div>
        <div class="mfg-capability-grid">
          <article class="mfg-capability-card">
            <span class="mfg-card-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8.5 3.5a5 5 0 0 1 7 7l-5 5a5 5 0 1 1-7-7l5-5Z"/><path d="m6 6 12 12"/></svg>
            </span>
            <h3>{{ __('manufacturing.products.title') }}</h3>
            <p>{{ __('manufacturing.products.text') }}</p>
            <ul class="mfg-check-list">
              @foreach(__('manufacturing.products.items') as $item)
                <li>{{ $item }}</li>
              @endforeach
            </ul>
          </article>
          <article class="mfg-capability-card">
            <span class="mfg-card-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16v13H4zM7 3h10v4H7z"/><path d="M8 11h8M8 15h5"/></svg>
            </span>
            <h3>{{ __('manufacturing.packaging.title') }}</h3>
            <p>{{ __('manufacturing.packaging.text') }}</p>
            <ul class="mfg-check-list">
              @foreach(__('manufacturing.packaging.items') as $item)
                <li>{{ $item }}</li>
              @endforeach
            </ul>
          </article>
        </div>
      </div>
    </section>

    <section class="mfg-section mfg-section--soft" aria-labelledby="mfg-process-title">
      <div class="container">
        <div class="mfg-section-head">
          <p class="mfg-eyebrow">{{ __('manufacturing.process.eyebrow') }}</p>
          <h2 class="mfg-heading" id="mfg-process-title">{{ __('manufacturing.process.title') }}</h2>
          <p class="mfg-lead">{{ __('manufacturing.process.text') }}</p>
        </div>
        <ol class="mfg-process-grid">
          @foreach(__('manufacturing.process.items') as $step)
            <li class="mfg-process-card">
              <h3>{{ $step['title'] }}</h3>
              <p>{{ $step['text'] }}</p>
            </li>
          @endforeach
        </ol>
        <!-- TODO(business approval): add standalone quality-control and final handover stages only after their wording and evidence are approved. -->
      </div>
    </section>

    <section class="mfg-section" aria-labelledby="mfg-capacity-title">
      <div class="container">
        <div class="mfg-capacity-panel">
          <p class="mfg-eyebrow">{{ __('manufacturing.capacity.eyebrow') }}</p>
          <h2 class="mfg-heading" id="mfg-capacity-title">{{ __('manufacturing.capacity.title') }}</h2>
          <p class="mfg-lead">{{ __('manufacturing.capacity.text') }}</p>
          <div class="mfg-capacity-grid">
            @foreach(__('manufacturing.capacity.items') as $capacity)
              <article class="mfg-capacity-card">
                <div class="mfg-capacity-card__number">
                  {{ $capacity['value'] }} <span class="mfg-capacity-card__unit">{{ $capacity['unit'] }}</span>
                </div>
                <div class="mfg-capacity-card__label">{{ $capacity['label'] }}</div>
              </article>
            @endforeach
          </div>
        </div>
      </div>
    </section>

    <section class="mfg-section mfg-section--soft" aria-labelledby="mfg-trust-title">
      <div class="container">
        <div class="mfg-section-head">
          <p class="mfg-eyebrow">{{ __('manufacturing.trust.eyebrow') }}</p>
          <h2 class="mfg-heading" id="mfg-trust-title">{{ __('manufacturing.trust.title') }}</h2>
        </div>
        <div class="mfg-trust-grid">
          @foreach(__('manufacturing.trust.cards') as $card)
            <article class="mfg-trust-card">
              <span class="mfg-trust-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 4.5 6v5.2c0 4.7 3.2 8.2 7.5 9.8 4.3-1.6 7.5-5.1 7.5-9.8V6L12 3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>
              </span>
              <h3>{{ $card['title'] }}</h3>
              <p>{{ $card['text'] }}</p>
            </article>
          @endforeach
        </div>

        <aside class="mfg-certificate-panel" aria-labelledby="mfg-certificates-title">
          <div>
            <h3 id="mfg-certificates-title">{{ __('manufacturing.certificates.title') }}</h3>
            @if(isset($certificates) && $certificates->isNotEmpty())
              <ul class="mfg-certificate-list">
                @foreach($certificates as $certificate)
                  @php $certificateName = $certificate->{'name_' . $locale} ?: $certificate->name_ru; @endphp
                  <li>
                    <a href="{{ route('certificates.show', ['certificate' => $certificate, 'slug' => \Illuminate\Support\Str::slug($certificateName)]) }}">
                      {{ $certificateName }}@if($certificate->number) — {{ $certificate->number }}@endif
                    </a>
                    @if($certificate->document_path)
                      · <a href="{{ media_url($certificate->document_path) }}" download>{{ __('manufacturing.certificates.download') }}</a>
                    @endif
                  </li>
                @endforeach
              </ul>
            @else
              <p>{{ __('manufacturing.certificates.empty') }}</p>
            @endif
          </div>
          <a class="mfg-button mfg-button--outline" href="{{ route('certificates') }}">{{ __('manufacturing.certificates.view_all') }}</a>
        </aside>
      </div>
    </section>

    @if(!empty($partners) && count($partners))
      <section class="mfg-section" aria-labelledby="mfg-partners-title">
        <div class="container mfg-partners" data-partners-carousel>
          <div class="mfg-section-head">
            <p class="mfg-eyebrow">{{ __('manufacturing.partners.eyebrow') }}</p>
            <h2 class="mfg-heading" id="mfg-partners-title">{{ __('manufacturing.partners.title') }}</h2>
            <p class="mfg-lead">{{ __('manufacturing.partners.text') }}</p>
          </div>
          <div class="mfg-partners__controls">
            <button class="mfg-carousel-button" type="button" data-carousel-previous aria-label="{{ __('manufacturing.partners.previous') }}" aria-controls="mfg-partners-track">
              <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <button class="mfg-carousel-button" type="button" data-carousel-next aria-label="{{ __('manufacturing.partners.next') }}" aria-controls="mfg-partners-track">
              <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </button>
          </div>
          <div class="mfg-partners__viewport" data-carousel-viewport tabindex="0" aria-labelledby="mfg-partners-title">
            <ul class="mfg-partners__track" id="mfg-partners-track">
              @foreach($partners as $partner)
                @php
                  $sourcePath = is_object($partner) ? $partner->path : $partner;
                  $sourceName = basename($sourcePath);
                  $meta = $partnerMeta[$sourceName] ?? null;
                  $partnerName = $meta['name'] ?? null;
                  $optimizedPath = 'partners/optimized/' . pathinfo($sourceName, PATHINFO_FILENAME) . '.webp';
                  $displayPath = file_exists(public_path($optimizedPath)) ? $optimizedPath : $sourcePath;
                  $partnerUrl = is_object($partner) ? $partner->url : null;
                @endphp
                <li class="mfg-partner-card">
                  @if($partnerUrl)
                    <a href="{{ $partnerUrl }}" target="_blank" rel="noopener noreferrer">
                  @endif
                  <img
                    src="{{ media_url($displayPath) }}"
                    width="{{ $meta['width'] ?? 360 }}"
                    height="{{ $meta['height'] ?? 180 }}"
                    alt="{{ $partnerName ? __('manufacturing.partners.logo_alt', ['name' => $partnerName]) : '' }}"
                    loading="lazy"
                    decoding="async"
                  />
                  @if($partnerUrl)
                    </a>
                  @endif
                </li>
              @endforeach
            </ul>
          </div>
        </div>
      </section>
    @endif

    <section class="mfg-cta-section" id="manufacturing-request" aria-labelledby="mfg-cta-title">
      <div class="container">
        <div class="mfg-cta">
          <div class="mfg-cta__copy">
            <p class="mfg-eyebrow">{{ __('manufacturing.cta.eyebrow') }}</p>
            <h2 class="mfg-heading" id="mfg-cta-title">{{ __('manufacturing.cta.title') }}</h2>
            <p class="mfg-lead">{{ __('manufacturing.cta.text') }}</p>
            <div class="mfg-cta__links">
              <a class="mfg-cta__contact" data-analytics="phone_click" href="tel:+998991018839">
                <span aria-hidden="true">☎</span>
                <span><small>{{ __('manufacturing.cta.phone_label') }}</small><br>+998 99 101 88 39</span>
              </a>
              <a class="mfg-cta__contact" data-analytics="email_click" href="mailto:neo_labs2019@mail.ru">
                <span aria-hidden="true">✉</span>
                <span><small>{{ __('manufacturing.cta.email_label') }}</small><br>neo_labs2019@mail.ru</span>
              </a>
            </div>
          </div>

          <form class="mfg-form" action="{{ route('contact.send') }}" method="post">
            @csrf
            <input type="hidden" name="form_context" value="manufacturing" />
            <h2>{{ __('manufacturing.form.title') }}</h2>
            <p class="mfg-form__intro">{{ __('manufacturing.form.text') }}</p>

            @if(session('contact_ok'))
              <div class="mfg-form__alert mfg-form__alert--success" role="status">{{ session('contact_ok') }}</div>
            @endif
            @if(session('contact_error'))
              <div class="mfg-form__alert mfg-form__alert--error" role="alert">{{ session('contact_error') }}</div>
            @endif
            @if($errors->any())
              <div class="mfg-form__alert mfg-form__alert--error" role="alert">
                {{ __('manufacturing.form.error_summary') }}
              </div>
            @endif

            <div class="mfg-honeypot" aria-hidden="true">
              <label for="mfg-website">Website</label>
              <input id="mfg-website" name="website" type="text" tabindex="-1" autocomplete="off" />
            </div>

            <div class="mfg-form__grid">
              <div class="mfg-field">
                <label for="mfg-name">{{ __('manufacturing.form.name') }} <span aria-hidden="true">*</span></label>
                <input id="mfg-name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="255" required aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" @error('name') aria-describedby="mfg-name-error" @enderror placeholder="{{ __('manufacturing.form.name_placeholder') }}" />
                @error('name')<p class="mfg-field__error" id="mfg-name-error">{{ $message }}</p>@enderror
              </div>
              <div class="mfg-field">
                <label for="mfg-company">{{ __('manufacturing.form.company') }}</label>
                <input id="mfg-company" name="company" type="text" value="{{ old('company') }}" autocomplete="organization" maxlength="255" aria-invalid="{{ $errors->has('company') ? 'true' : 'false' }}" @error('company') aria-describedby="mfg-company-error" @enderror placeholder="{{ __('manufacturing.form.company_placeholder') }}" />
                @error('company')<p class="mfg-field__error" id="mfg-company-error">{{ $message }}</p>@enderror
              </div>
              <div class="mfg-field">
                <label for="mfg-contact">{{ __('manufacturing.form.contact') }} <span aria-hidden="true">*</span></label>
                <input id="mfg-contact" name="contact" type="text" value="{{ old('contact') }}" maxlength="255" required aria-invalid="{{ $errors->has('contact') ? 'true' : 'false' }}" @error('contact') aria-describedby="mfg-contact-error" @enderror placeholder="{{ __('manufacturing.form.contact_placeholder') }}" />
                @error('contact')<p class="mfg-field__error" id="mfg-contact-error">{{ $message }}</p>@enderror
              </div>
              <div class="mfg-field">
                <label for="mfg-product">{{ __('manufacturing.form.product_type') }} <span aria-hidden="true">*</span></label>
                <select id="mfg-product" name="product_type" required aria-invalid="{{ $errors->has('product_type') ? 'true' : 'false' }}" @error('product_type') aria-describedby="mfg-product-error" @enderror>
                  <option value="">{{ __('manufacturing.form.product_placeholder') }}</option>
                  @foreach(__('manufacturing.form.product_options') as $option)
                    <option value="{{ $option }}" {{ old('product_type') === $option ? 'selected' : '' }}>{{ $option }}</option>
                  @endforeach
                </select>
                @error('product_type')<p class="mfg-field__error" id="mfg-product-error">{{ $message }}</p>@enderror
              </div>
              <div class="mfg-field mfg-field--wide">
                <label for="mfg-message">{{ __('manufacturing.form.message') }} <span aria-hidden="true">*</span></label>
                <textarea id="mfg-message" name="message" minlength="10" maxlength="2000" required aria-invalid="{{ $errors->has('message') ? 'true' : 'false' }}" @error('message') aria-describedby="mfg-message-error" @enderror placeholder="{{ __('manufacturing.form.message_placeholder') }}">{{ old('message') }}</textarea>
                @error('message')<p class="mfg-field__error" id="mfg-message-error">{{ $message }}</p>@enderror
              </div>
            </div>
            <button class="mfg-button" type="submit">{{ __('manufacturing.form.submit') }}</button>
          </form>
        </div>
      </div>
    </section>
  </main>
</x-layouts.index>
