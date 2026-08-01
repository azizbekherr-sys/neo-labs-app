@props([
  'title' => null,
  'description' => null,
  'keywords' => null,
  'image' => null,
  'robots' => null,
  'canonical' => null,
  'schema' => null,
  'breadcrumbs' => [],
  'ogType' => 'website',
  'ogTitle' => null,
  'ogDescription' => null,
  'imageAlt' => null,
  'prev' => null,
  'next' => null,
  'preloadImage' => null,
])
<!DOCTYPE html>
<html lang="{{ in_array(app()->getLocale(), ['ru','uz','en']) ? app()->getLocale() : 'ru' }}">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="analytics-endpoint" content="{{ route('analytics.collect', [], false) }}" />
    @php
      $appName = config('seo.site_name', 'NEO-LABS');
      $appUrl = \App\Support\Seo::baseUrl();
      $locale = \App\Support\Seo::locale();
      $logoUrl = \App\Support\Seo::absolute(config('seo.logo'));
      $homeTitle = config("seo.home_titles.{$locale}");
      $requestedTitle = trim((string) ($title ?: $homeTitle));
      $pageTitle = \Illuminate\Support\Str::contains(mb_strtoupper($requestedTitle), $appName)
        ? $requestedTitle
        : $requestedTitle . ' — ' . $appName;
      $metaDesc = trim((string) ($description ?: config("seo.descriptions.{$locale}")));
      $metaKeywords = $keywords;
      $isFiltered = request()->hasAny(['q', 'filter', 'sort', 'category']);
      $isUnsupportedLocale = !in_array(app()->getLocale(), config('seo.locales', ['ru','uz','en']), true);
      $robotsValue = $robots ?: (($isFiltered || $isUnsupportedLocale)
        ? 'noindex, follow, max-image-preview:large'
        : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');
      $canonicalUrl = \App\Support\Seo::canonical($canonical);
      $ogImage = \App\Support\Seo::assetUrl($image ?: config('seo.default_image'));
      try {
        $altRu = \App\Support\Seo::alternate('ru');
        $altUz = \App\Support\Seo::alternate('uz');
        $altEn = \App\Support\Seo::alternate('en');
      } catch (\Throwable $e) {
        $altRu = $appUrl . '/ru';
        $altUz = $appUrl . '/uz';
        $altEn = $appUrl . '/en';
      }
      $crumbs = $breadcrumbs ?: [[
        'name' => ['ru' => 'Главная', 'uz' => 'Bosh sahifa', 'en' => 'Home'][$locale],
        'url' => '/' . $locale,
      ]];
      if (!request()->routeIs('home') && count($crumbs) === 1) {
        $crumbs[] = ['name' => $requestedTitle, 'url' => $canonicalUrl];
      }
      $extraSchemas = [];
      if (is_string($schema) && $schema !== '') {
        $decoded = json_decode($schema, true);
        if (is_array($decoded)) $extraSchemas[] = $decoded;
      } elseif (is_array($schema)) {
        if (isset($schema['@context'])) unset($schema['@context']);
        if (isset($schema['@graph']) && is_array($schema['@graph'])) {
          $extraSchemas = $schema['@graph'];
        } else {
          $extraSchemas[] = $schema;
        }
      }
      $jsonLd = \App\Support\Seo::graph($pageTitle, $metaDesc, $canonicalUrl, $crumbs, $extraSchemas);
    @endphp
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDesc }}" />
    @if($metaKeywords)<meta name="keywords" content="{{ $metaKeywords }}" />@endif
    <meta name="author" content="{{ $appName }}" />
    <meta name="robots" content="{{ $robotsValue }}" />
    <meta name="googlebot" content="{{ $robotsValue }}" />
    <link rel="canonical" href="{{ $canonicalUrl }}" />
    <link rel="alternate" hreflang="ru-UZ" href="{{ $altRu }}" />
    <link rel="alternate" hreflang="uz-UZ" href="{{ $altUz }}" />
    <link rel="alternate" hreflang="en-UZ" href="{{ $altEn }}" />
    @php
      $defaultAlternate = ['uz' => $altUz, 'ru' => $altRu, 'en' => $altEn][config('seo.default_locale', 'uz')] ?? $altUz;
    @endphp
    <link rel="alternate" hreflang="x-default" href="{{ $defaultAlternate }}" />
    @if($prev)<link rel="prev" href="{{ \App\Support\Seo::absolute($prev) }}" />@endif
    @if($next)<link rel="next" href="{{ \App\Support\Seo::absolute($next) }}" />@endif
    <meta name="theme-color" content="#176b35" />
    @if(config('seo.verification.google'))<meta name="google-site-verification" content="{{ config('seo.verification.google') }}" />@endif
    @if(config('seo.verification.bing'))<meta name="msvalidate.01" content="{{ config('seo.verification.bing') }}" />@endif
    @if(config('seo.verification.yandex'))<meta name="yandex-verification" content="{{ config('seo.verification.yandex') }}" />@endif
    <!-- Open Graph -->
    <meta property="og:type" content="{{ $ogType }}" />
    <meta property="og:site_name" content="{{ $appName }}" />
    <meta property="og:title" content="{{ trim((string) $ogTitle) ?: $pageTitle }}" />
    <meta property="og:description" content="{{ trim((string) $ogDescription) ?: $metaDesc }}" />
    <meta property="og:url" content="{{ $canonicalUrl }}" />
    <meta property="og:image" content="{{ $ogImage }}" />
    <meta property="og:image:alt" content="{{ trim((string) $imageAlt) ?: $pageTitle }}" />
    @php $ogLocaleMap = ['uz' => 'uz_UZ', 'ru' => 'ru_RU', 'en' => 'en_US']; @endphp
    <meta property="og:locale" content="{{ $ogLocaleMap[$locale] ?? 'ru_RU' }}" />
    @foreach($ogLocaleMap as $ogLocKey => $ogLocVal)
      @if($ogLocKey !== $locale)<meta property="og:locale:alternate" content="{{ $ogLocVal }}" />@endif
    @endforeach
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $pageTitle }}" />
    <meta name="twitter:description" content="{{ $metaDesc }}" />
    <meta name="twitter:image" content="{{ $ogImage }}" />
    @if($preloadImage)<link rel="preload" as="image" href="{{ \App\Support\Seo::assetUrl($preloadImage) }}" fetchpriority="high" />@endif
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('img/icon-192.png') }}" />
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('img/icon-192.png') }}" />
    <link rel="shortcut icon" href="{{ asset('img/icon-192.png') }}" />
    <link rel="manifest" href="{{ asset('site.webmanifest') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Raleway:wght@400;600;700;800&display=swap"
      rel="stylesheet"
    />
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @stack('schema')
    @if(config('seo.analytics.ga_id'))
      <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('seo.analytics.ga_id') }}"></script>
      <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json(config('seo.analytics.ga_id')), {anonymize_ip: true});
      </script>
    @endif
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}" />
    @stack('head')
  </head>

  <body>
    <a class="skip-link" href="#site-content">{{ __('site.common.skip_to_content') }}</a>
    @if(session('contact_ok'))
      <div class="toast" id="globalToast">{{ session('contact_ok') }}</div>
    @endif
    <header>
      <div class="container top-bar">
        <a class="logo" href="{{ route('home') }}">
          <img src="{{ asset('img/logo.png') }}" width="229" height="49" alt="{{ __('Neo-labs') }}" />
        </a>
        <button class="menu-toggle" type="button" aria-label="{{ __('site.common.menu_open') }}" data-label-open="{{ __('site.common.menu_open') }}" data-label-close="{{ __('site.common.menu_close') }}" aria-expanded="false" aria-controls="siteNav"><span></span></button>
        <div class="nav-wrapper" id="siteNav" aria-label="{{ __('Меню') }}" tabindex="-1">
          <nav>
            <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>{{ __('Главная') }}</a>
            <a class="{{ request()->routeIs('catalog') ? 'active' : '' }}" href="{{ route('catalog') }}" @if(request()->routeIs('catalog')) aria-current="page" @endif>{{ __('Препараты') }}</a>
            <a class="{{ request()->routeIs('manufacturing') ? 'active' : '' }}" href="{{ route('manufacturing') }}" @if(request()->routeIs('manufacturing')) aria-current="page" @endif>{{ __('Контрактное производство') }}</a>
            <a class="{{ request()->routeIs('articles') ? 'active' : '' }}" href="{{ route('articles') }}" @if(request()->routeIs('articles')) aria-current="page" @endif>{{ __('Статьи') }}</a>
            <a class="{{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}" @if(request()->routeIs('about')) aria-current="page" @endif>{{ __('О нас') }}</a>
            <a class="{{ request()->routeIs('contacts') ? 'active' : '' }}" href="{{ route('contacts') }}" @if(request()->routeIs('contacts')) aria-current="page" @endif>{{ __('Контакты') }}</a>
          </nav>
        </div>
        <div class="lang-group">
          @php
            $langOptions = [
              'uz' => ['code' => 'UZ', 'flag' => 'uz.png'],
              'ru' => ['code' => 'RU', 'flag' => 'ru.png'],
              'en' => ['code' => 'EN', 'flag' => 'en.png'],
            ];
            $currentLocaleRaw = app()->getLocale();
            $currentLocale = in_array($currentLocaleRaw, ['ru', 'uz', 'en']) ? $currentLocaleRaw : 'ru';
            $currentCode = $langOptions[$currentLocale]['code'];
            $currentFlag = $langOptions[$currentLocale]['flag'];
          @endphp
          <button class="lang-switch" type="button" aria-haspopup="true" aria-expanded="false">
            <img src="{{ asset('img/' . $currentFlag) }}" width="18" height="12" alt="{{ $currentCode }}" style="width:18px;height:12px;object-fit:cover;border-radius:2px;">
            <span class="lang-current">{{ $currentCode }}</span> ▾
          </button>
          <div class="lang-menu" role="menu">
            @foreach($langOptions as $lc => $opt)
              @if($lc !== $currentLocale)
                @php $languageUrls = ['uz' => $altUz, 'ru' => $altRu, 'en' => $altEn]; @endphp
                <a href="{{ $languageUrls[$lc] }}" role="menuitem" lang="{{ $lc }}" hreflang="{{ $lc }}">
                  <span style="display:inline-flex;align-items:center;gap:8px;">
                    <img src="{{ asset('img/' . $opt['flag']) }}" width="18" height="12" alt="{{ $opt['code'] }}" style="width:18px;height:12px;object-fit:cover;border-radius:2px;"> <span>{{ $opt['code'] }}</span>
                  </span>
                </a>
              @endif
            @endforeach
          </div>
        </div>
      </div>
    </header>
    <div class="menu-backdrop" id="menuBackdrop" aria-hidden="true"></div>

    @if(!request()->routeIs('home'))
      <nav class="site-breadcrumb" aria-label="{{ __('site.common.breadcrumb') }}">
        <ol class="container">
          @foreach($crumbs as $crumb)
            <li @if($loop->last) aria-current="page" @endif>
              @if(!$loop->last)
                @php $visibleCrumbPath = parse_url($crumb['url'], PHP_URL_PATH) ?: '/'; @endphp
                <a href="{{ url($visibleCrumbPath) }}">{{ $crumb['name'] }}</a>
              @else<span>{{ $crumb['name'] }}</span>@endif
            </li>
          @endforeach
        </ol>
      </nav>
    @endif

    <div id="site-content" tabindex="-1">
      {{ $slot }}
    </div>

    <footer class="site-footer">
      <div class="site-footer__container">
        <div class="site-footer__grid">
          <div class="site-footer__brand">
            <a class="site-footer__logo" href="{{ route('home') }}" aria-label="NEO-LABS">
              <img src="{{ asset('img/logo.png') }}" width="229" height="49" alt="NEO-LABS" loading="lazy" decoding="async">
            </a>
            <p>{{ __('site.footer.brand_description') }}</p>
          </div>

          <section class="site-footer__column" aria-labelledby="footer-contact-title">
            <h2 id="footer-contact-title">{{ __('site.footer.contact_title') }}</h2>
            <ul class="site-footer__contact-list">
              <li>
                <svg class="site-footer__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7.5 3.5 10 8l-2.2 1.8a16.2 16.2 0 0 0 6.4 6.4L16 14l4.5 2.5v3A1.5 1.5 0 0 1 19 21C10.2 21 3 13.8 3 5a1.5 1.5 0 0 1 1.5-1.5h3Z"/></svg>
                <a data-analytics="phone_click" href="tel:+998991018839"><small>{{ __('site.footer.phone_label') }}</small><strong>+998 99 101 88 39</strong></a>
              </li>
              <li>
                <svg class="site-footer__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3.5 5.5h17v13h-17z"/><path d="m4 6 8 6 8-6"/></svg>
                <a data-analytics="email_click" href="mailto:neo_labs2019@mail.ru"><small>{{ __('site.footer.email_label') }}</small><strong>neo_labs2019@mail.ru</strong></a>
              </li>
              <li>
                <svg class="site-footer__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
                <span><small>{{ __('site.footer.hours_label') }}</small><strong>{{ __('site.footer.hours_value') }}</strong></span>
              </li>
            </ul>
          </section>

          <section class="site-footer__column" aria-labelledby="footer-address-title">
            <h2 id="footer-address-title">{{ __('site.footer.address_title') }}</h2>
            <address class="site-footer__address">
              <svg class="site-footer__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
              <p>{{ __('site.footer.address_value') }}</p>
            </address>
            <a class="site-footer__map-link" href="{{ route('contacts') }}#contacts-map">
              {{ __('site.footer.view_map') }}
              <span aria-hidden="true">→</span>
            </a>
          </section>

          <nav class="site-footer__column site-footer__navigation" aria-labelledby="footer-navigation-title" aria-label="{{ __('site.footer.navigation_aria') }}">
            <h2 id="footer-navigation-title">{{ __('site.footer.navigation_title') }}</h2>
            <div class="site-footer__links">
              <a href="{{ route('home') }}">{{ __('Главная') }}</a>
              <a href="{{ route('catalog') }}">{{ __('Препараты') }}</a>
              <a href="{{ route('manufacturing') }}">{{ __('Контрактное производство') }}</a>
              <a href="{{ route('articles') }}">{{ __('Статьи') }}</a>
              <a href="{{ route('certificates') }}">{{ __('site.common.certificates') }}</a>
              <a href="{{ route('about') }}">{{ __('О нас') }}</a>
              <a href="{{ route('contacts') }}">{{ __('Контакты') }}</a>
              <a href="{{ route('privacy') }}">{{ __('site.common.privacy') }}</a>
            </div>
          </nav>
        </div>

        <div class="site-footer__bottom">
          <p class="site-footer__copyright">© {{ now()->year }} {{ __('site.about.legal_name_display') }}</p>
          <p class="site-footer__disclaimer">{{ __('site.editorial.disclaimer') }}</p>
        </div>
      </div>
    </footer>
    <script>
      (function () {
        var endpointMeta = document.querySelector('meta[name="analytics-endpoint"]');
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var disabled = !endpointMeta || navigator.doNotTrack === '1' || window.doNotTrack === '1';

        function makeId() {
          if (window.crypto && typeof window.crypto.randomUUID === 'function') return window.crypto.randomUUID();
          return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (character) {
            var random = Math.random() * 16 | 0;
            var value = character === 'x' ? random : (random & 0x3 | 0x8);
            return value.toString(16);
          });
        }

        function stored(storage, key) {
          try {
            var current = storage.getItem(key);
            if (current) return current;
            current = makeId();
            storage.setItem(key, current);
            return current;
          } catch (error) {
            return makeId();
          }
        }

        var visitorId = stored(window.localStorage, 'nl_analytics_visitor');
        var sessionId = stored(window.sessionStorage, 'nl_analytics_session');
        var query = new URLSearchParams(window.location.search);
        var clientTimezone = '';
        try { clientTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (error) {}
        var attribution = null;
        try { attribution = JSON.parse(window.sessionStorage.getItem('nl_analytics_attribution') || 'null'); } catch (error) {}
        if (!attribution) {
          var initialReferrer = document.referrer || '';
          try { if (initialReferrer && new URL(initialReferrer).host === window.location.host) initialReferrer = ''; } catch (error) {}
          attribution = {
            referrer: initialReferrer,
            source: query.get('utm_source') || (query.get('gclid') ? 'google' : ''),
            medium: query.get('utm_medium') || (query.get('gclid') ? 'cpc' : ''),
            campaign: query.get('utm_campaign') || '',
            landing_path: window.location.pathname
          };
          try { window.sessionStorage.setItem('nl_analytics_attribution', JSON.stringify(attribution)); } catch (error) {}
        }

        function send(eventType, details) {
          if (disabled) return;
          details = details || {};
          var payload = {
            event_id: makeId(),
            visitor_id: visitorId,
            session_id: sessionId,
            event_type: eventType || 'page_view',
            path: window.location.pathname,
            title: document.title,
            landing_path: attribution.landing_path || window.location.pathname,
            referrer: attribution.referrer || '',
            utm_source: attribution.source || '',
            utm_medium: attribution.medium || '',
            utm_campaign: attribution.campaign || '',
            target_url: details.link_url || details.target_url || '',
            screen_width: Math.min(65535, window.screen && window.screen.width || window.innerWidth || 0),
            screen_height: Math.min(65535, window.screen && window.screen.height || window.innerHeight || 0),
            client_language: navigator.language || '',
            timezone: clientTimezone
          };
          window.fetch(endpointMeta.content, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : '',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
          }).catch(function () {});
        }

        window.NeoAnalytics = {trackEvent: send};
        var recordPageView = function () { send('page_view'); };
        if ('requestIdleCallback' in window) window.requestIdleCallback(recordPageView, {timeout: 1600});
        else window.setTimeout(recordPageView, 900);
      })();

      window.dataLayer = window.dataLayer || [];
      function trackSeoEvent(name, details) {
        window.dataLayer.push(Object.assign({event: name}, details || {}));
        if (window.NeoAnalytics) window.NeoAnalytics.trackEvent(name, details || {});
      }
      document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!link) return;
        var href = link.getAttribute('href') || '';
        var named = link.getAttribute('data-analytics');
        var eventName = named || (href.indexOf('tel:') === 0 ? 'phone_click' : (href.indexOf('mailto:') === 0 ? 'email_click' : null));
        if (!eventName && /telegram|t\.me|instagram|facebook|linkedin|youtube/i.test(href)) eventName = 'social_click';
        if (!eventName && /^https?:\/\//i.test(href)) {
          try { if (new URL(href, window.location.href).host !== window.location.host) eventName = 'outbound_click'; } catch (error) {}
        }
        if (eventName) trackSeoEvent(eventName, {link_url: href, page_path: window.location.pathname});
      });
      document.addEventListener('submit', function (event) {
        if (event.target && event.target.matches('form[action*="contact/send"]')) {
          trackSeoEvent('contact_form_submit', {page_path: window.location.pathname});
        }
      });
      window.addEventListener("load", function () {
        // Toast show/hide
        var toast = document.getElementById('globalToast');
        if (toast) {
          setTimeout(function(){ toast.classList.add('show'); }, 120);
          setTimeout(function(){ toast.classList.remove('show'); }, 3800);
        }
        // Jivo adds several third-party JS/CSS/audio requests. Start it only
        // after the page has settled so it cannot compete with critical assets.
        var loadJivo = function () {
          if (document.querySelector('script[data-jivo-widget]')) return;
          var jivo = document.createElement('script');
          jivo.src = 'https://code.jivosite.com/widget/2KdGV1dr1B';
          jivo.async = true;
          jivo.dataset.jivoWidget = 'true';
          document.body.appendChild(jivo);

          var reserveFooterSpace = function () {
            if (!document.getElementById('jivo-iframe-container')) return false;
            document.documentElement.style.setProperty('--site-chat-reserve', '52px');
            return true;
          };
          if (!reserveFooterSpace()) {
            var footerChatObserver = new MutationObserver(function () {
              if (reserveFooterSpace()) footerChatObserver.disconnect();
            });
            footerChatObserver.observe(document.body, {childList: true, subtree: true});
          }
        };
        setTimeout(function () {
          if ('requestIdleCallback' in window) {
            window.requestIdleCallback(loadJivo, {timeout: 2500});
          } else {
            loadJivo();
          }
        }, 6000);
      });
      // Mobile menu toggle
      (function () {
        var toggle = document.querySelector('.menu-toggle');
        var nav = document.getElementById('siteNav');
        var headerRef = document.querySelector('header');
        var backdrop = document.getElementById('menuBackdrop');
        var previouslyFocused = null;
        var focusableSelectors = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';
        var keydownHandlerBound = null;
        var touchMoveLockHandler = null;
        var openLabel = toggle ? toggle.getAttribute('data-label-open') : '';
        var closeLabel = toggle ? toggle.getAttribute('data-label-close') : '';
        if (!toggle || !nav) return;
        function positionNav() {
          try {
            var h = headerRef ? headerRef.getBoundingClientRect().height : 64;
            nav.style.top = (h + 8) + 'px';
          } catch(e) {}
        }
        function getFocusable() {
          return nav.querySelectorAll(focusableSelectors);
        }
        function trapFocusKeydown(e) {
          if (e.key !== 'Tab') return;
          var focusable = getFocusable();
          if (!focusable.length) { e.preventDefault(); return; }
          var first = focusable[0];
          var last = focusable[focusable.length - 1];
          if (e.shiftKey) {
            if (document.activeElement === first || !nav.contains(document.activeElement)) {
              e.preventDefault();
              last.focus();
            }
          } else {
            if (document.activeElement === last) {
              e.preventDefault();
              first.focus();
            }
          }
        }
        function lockBodyScroll() {
          if (touchMoveLockHandler) return;
          touchMoveLockHandler = function (e) {
            if (!nav.contains(e.target)) {
              e.preventDefault();
            }
          };
          document.addEventListener('touchmove', touchMoveLockHandler, { passive: false });
        }
        function unlockBodyScroll() {
          if (!touchMoveLockHandler) return;
          document.removeEventListener('touchmove', touchMoveLockHandler);
          touchMoveLockHandler = null;
        }
        function closeMenu() {
          var wasOpen = nav.classList.contains('open');
          nav.classList.remove('open');
          toggle.classList.remove('open');
          toggle.setAttribute('aria-expanded', 'false');
          toggle.setAttribute('aria-label', openLabel || 'Open menu');
          nav.removeAttribute('role');
          nav.removeAttribute('aria-modal');
          document.body.classList.remove('menu-open');
          if (backdrop) backdrop.classList.remove('show');
          if (backdrop) backdrop.setAttribute('aria-hidden', 'true');
          if (keydownHandlerBound) {
            document.removeEventListener('keydown', keydownHandlerBound);
            keydownHandlerBound = null;
          }
          unlockBodyScroll();
          try { if (wasOpen && previouslyFocused) previouslyFocused.focus(); } catch(e) {}
        }
        function openMenu() {
          positionNav();
          nav.classList.add('open');
          toggle.classList.add('open');
          toggle.setAttribute('aria-expanded', 'true');
          toggle.setAttribute('aria-label', closeLabel || 'Close menu');
          nav.setAttribute('role', 'dialog');
          nav.setAttribute('aria-modal', 'true');
          document.body.classList.add('menu-open');
          if (backdrop) backdrop.classList.add('show');
          if (backdrop) backdrop.setAttribute('aria-hidden', 'false');
          previouslyFocused = document.activeElement;
          // focus first focusable item
          setTimeout(function () {
            var focusable = getFocusable();
            if (focusable.length) {
              try { nav.focus(); } catch(e) {}
              try { focusable[0].focus(); } catch(e) {}
            } else {
              try { nav.focus(); } catch(e) {}
            }
          }, 10);
          // bind focus trap
          keydownHandlerBound = trapFocusKeydown;
          document.addEventListener('keydown', keydownHandlerBound);
          // mobile body scroll lock
          lockBodyScroll();
        }
        // ensure toggler always on top for tapping
        toggle.style.zIndex = 2100;
        toggle.addEventListener('click', function () {
          if (nav.classList.contains('open')) { closeMenu(); } else { openMenu(); }
        });
        // Reliable navigation on mobile - programmatic redirect
        nav.addEventListener('click', function (e) {
          var link = e.target.closest('a');
          if (!link) return;
          if (window.innerWidth <= 960) {
            e.preventDefault();
            var href = link.getAttribute('href');
            closeMenu();
            setTimeout(function(){ window.location.href = href; }, 60);
          }
        });
        if (backdrop) backdrop.addEventListener('click', closeMenu);
        window.addEventListener('keyup', function (e) {
          if (e.key === 'Escape' && nav.classList.contains('open')) closeMenu();
        });
        window.addEventListener('resize', function () {
          positionNav();
          if (window.innerWidth > 960) { closeMenu(); }
        });
        positionNav();
      })();

      // Language dropdown (open/close only, switch happens by link)
      (function () {
        var langGroup = document.querySelector('.lang-group');
        if (!langGroup) return;
        var btn = langGroup.querySelector('.lang-switch');
        var menu = langGroup.querySelector('.lang-menu');
        function close() {
          langGroup.classList.remove('open');
          btn && btn.setAttribute('aria-expanded', 'false');
        }
        function open() {
          langGroup.classList.add('open');
          btn && btn.setAttribute('aria-expanded', 'true');
        }
        btn && btn.addEventListener('click', function (e) {
          e.stopPropagation();
          if (langGroup.classList.contains('open')) { close(); } else { open(); }
        });
        document.addEventListener('click', function (e) {
          if (!langGroup.contains(e.target)) close();
        });
      })();
    </script>
    @stack('scripts')
  </body>
</html>
