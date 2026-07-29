@php
  $locale = in_array(app()->getLocale(), ['uz', 'ru', 'en'], true) ? app()->getLocale() : 'ru';
  $pick = fn (string $field) => \App\Support\Content::localized($product, $field, $locale);
  $name = trim((string) ($pick('name') ?: 'NEO-LABS'));
  $categoryName = $product->category ? $product->category->localizedName($locale) : null;
  $type = trim((string) ($categoryName ?: $pick('type') ?: $pick('form') ?: 'NEO-LABS'));
  $shortDescription = \App\Support\Content::excerpt($pick('short_description') ?: $pick('description') ?: $pick('composition'), 220);
  $benefits = $product->localizedBenefits($locale);
  $descriptionHtml = \App\Support\Content::sanitize($pick('description'));
  $compositionHtml = \App\Support\Content::sanitize($pick('composition'));
  $applicationHtml = \App\Support\Content::sanitize($pick('application'));
  $warningsHtml = \App\Support\Content::sanitize($pick('warnings'));
  $storageHtml = \App\Support\Content::sanitize($pick('storage_conditions'));
  $disclaimer = trim((string) ($pick('disclaimer') ?: __('content.product.disclaimer')));
  $salesMode = $product->effective_sales_mode;

  $imagePaths = collect(is_array($product->images) ? $product->images : [$product->image])->filter()->unique()->values();
  if ($imagePaths->isEmpty()) $imagePaths = collect([null]);
  $gallery = $imagePaths->map(fn ($path) => \App\Support\Media::responsive($path, 'products', [320, 640, 960, 1280]))->values();
  $primaryMedia = $gallery->first();

  $slug = \Illuminate\Support\Str::slug($name ?: 'product-' . $product->id);
  $autoCanonical = \App\Support\Seo::route('product.show', ['locale' => $locale, 'product' => $product, 'slug' => $slug]);
  $customCanonicalPath = $product->seo_override && $product->canonical_url ? parse_url($product->canonical_url, PHP_URL_PATH) : null;
  $canonical = ($customCanonicalPath && \Illuminate\Support\Str::startsWith($customCanonicalPath, '/'.$locale.'/')) ? $product->canonical_url : $autoCanonical;
  $metaSource = $product->seo_override ? $pick('meta_description') : null;
  $metaDescription = \App\Support\Content::excerpt($metaSource ?: $shortDescription ?: $disclaimer, 155);

  $formValue = trim((string) ($pick('form') ?: $product->form));
  $typeParts = preg_split('/,\s*/u', $type, -1, PREG_SPLIT_NO_EMPTY) ?: [];
  $seoQualifier = \App\Support\Content::excerpt($formValue ?: (end($typeParts) ?: $type), 40);
  $autoSeoTitle = $name . (($seoQualifier && mb_strtolower($seoQualifier) !== mb_strtolower($name)) ? ' — ' . $seoQualifier : '');
  $packageValue = trim((string) ($pick('packaging_count') ?: $product->package));
  $shelfLife = trim((string) $pick('shelf_life'));
  $registration = trim((string) $pick('registration_info'));
  $manufacturer = trim((string) ($product->manufacturer ?: config('seo.site_name', 'NEO-LABS')));
  $countryMap = [
    'UZ' => ['uz' => 'O‘zbekiston', 'ru' => 'Узбекистан', 'en' => 'Uzbekistan'],
  ];
  $countryCode = strtoupper((string) ($product->country_of_origin ?: 'UZ'));
  $country = $countryMap[$countryCode][$locale] ?? $countryCode;

  $faqs = [];
  foreach ([$locale, 'uz', 'ru', 'en'] as $faqLocale) {
    $candidate = $product->{'faqs_'.$faqLocale};
    if (is_array($candidate) && collect($candidate)->filter(fn($faq) => trim((string)($faq['question'] ?? '')) !== '' && trim((string)($faq['answer'] ?? '')) !== '')->isNotEmpty()) {
      $faqs = collect($candidate)->filter(fn($faq) => trim((string)($faq['question'] ?? '')) !== '' && trim((string)($faq['answer'] ?? '')) !== '')->values()->all();
      break;
    }
  }

  $availabilityLabels = [
    'in_stock' => __('content.product.in_stock'),
    'out_of_stock' => __('content.product.out_of_stock'),
    'preorder' => __('content.product.preorder'),
  ];
  $availabilitySchema = [
    'in_stock' => 'https://schema.org/InStock',
    'out_of_stock' => 'https://schema.org/OutOfStock',
    'preorder' => 'https://schema.org/PreOrder',
  ];
  $hasRealOffer = $salesMode === 'direct' && $product->price !== null && (float)$product->price > 0 && $product->currency;
  $priceLabel = $hasRealOffer ? number_format((float)$product->price, 0, '.', ' ') . ' ' . $product->currency : null;

  $ctaHref = route('contacts') . '?product=' . urlencode($name);
  $ctaLabel = __('content.product.contact_consult');
  $ctaExternal = false;
  if ($salesMode === 'external' && $product->external_purchase_url) {
    $ctaHref = $product->external_purchase_url;
    $ctaLabel = __('content.product.external_buy');
    $ctaExternal = true;
  } elseif ($salesMode === 'direct') {
    $ctaLabel = __('content.product.direct_buy');
  }

  $additional = [];
  foreach (array_filter([
    __('content.product.form') => $formValue,
    __('content.product.package') => $packageValue,
    __('content.product.shelf_life') => $shelfLife,
    __('content.product.registration') => $registration,
  ]) as $label => $value) {
    $additional[] = ['@type' => 'PropertyValue', 'name' => $label, 'value' => \App\Support\Content::plain($value)];
  }

  $productSchema = \App\Support\Seo::clean([
    '@type' => 'Product',
    '@id' => $canonical . '#product',
    'name' => $name,
    'description' => ($product->seo_override ? $pick('schema_description') : null) ?: $metaDescription,
    'image' => collect($gallery)->pluck('fallback')->filter()->all(),
    'sku' => $product->sku,
    'gtin13' => preg_match('/^\d{13}$/', (string) $product->barcode) ? $product->barcode : null,
    'category' => $type,
    'brand' => ['@type' => 'Brand', 'name' => 'NEO-LABS'],
    'manufacturer' => $manufacturer ? ['@type' => 'Organization', 'name' => $manufacturer] : null,
    'offers' => $hasRealOffer ? [
      '@type' => 'Offer',
      'url' => $canonical,
      'price' => number_format((float)$product->price, 2, '.', ''),
      'priceCurrency' => $product->currency,
      'availability' => $availabilitySchema[$product->stock_status] ?? null,
    ] : null,
    'additionalProperty' => $additional,
    'mainEntityOfPage' => $canonical . '#webpage',
    'url' => $canonical,
  ]);
  $faqSchema = $faqs ? [
    '@type' => 'FAQPage',
    '@id' => $canonical . '#faq',
    'mainEntity' => collect($faqs)->map(fn($faq) => [
      '@type' => 'Question',
      'name' => \App\Support\Content::plain($faq['question']),
      'acceptedAnswer' => ['@type' => 'Answer', 'text' => \App\Support\Content::plain($faq['answer'])],
    ])->all(),
  ] : null;
  $schema = ['@graph' => array_values(array_filter([$productSchema, $faqSchema]))];
  $breadcrumbs = [
    ['name' => __('site.common.home'), 'url' => '/' . $locale],
    ['name' => __('content.catalog.title'), 'url' => '/' . $locale . '/catalog'],
    ['name' => $name, 'url' => $canonical],
  ];
@endphp

<x-layouts.index
  :title="($product->seo_override ? $pick('seo_title') : null) ?: $autoSeoTitle"
  :description="$metaDescription"
  :image="($product->seo_override ? $product->og_image : null) ?: $primaryMedia['fallback']"
  :canonical="$canonical"
  :robots="$product->robots"
  :schema="$schema"
  :breadcrumbs="$breadcrumbs"
  og-type="product"
  :preload-image="$primaryMedia['src']"
>
  @push('head')
    <link rel="stylesheet" href="{{ asset('css/content-pages.css') }}?v={{ filemtime(public_path('css/content-pages.css')) }}">
  @endpush

  <main class="content-page product-detail has-sticky-cta" id="main-content">
    <div class="container">
      <article>
        <section class="product-intro" aria-labelledby="product-title">
          <div class="product-gallery" data-product-gallery>
            <div class="product-visual__frame{{ $imagePaths->first() ? '' : ' is-fallback' }}">
              <picture data-gallery-picture>
                @if($primaryMedia['avif_srcset'])<source type="image/avif" srcset="{{ $primaryMedia['avif_srcset'] }}" sizes="(min-width:900px) 46vw, calc(100vw - 32px)" data-gallery-avif>@endif
                @if($primaryMedia['srcset'])<source type="image/webp" srcset="{{ $primaryMedia['srcset'] }}" sizes="(min-width:900px) 46vw, calc(100vw - 32px)" data-gallery-webp>@endif
                <img
                  src="{{ $primaryMedia['fallback'] }}"
                  @if($primaryMedia['srcset']) srcset="{{ $primaryMedia['srcset'] }}" sizes="(min-width:900px) 46vw, calc(100vw - 32px)" @endif
                  width="{{ $primaryMedia['width'] }}"
                  height="{{ $primaryMedia['height'] }}"
                  alt="{{ __('content.product.image_alt', ['name' => $name, 'number' => 1]) }}"
                  loading="eager"
                  fetchpriority="high"
                  decoding="async"
                  data-gallery-image
                >
              </picture>
            </div>
            @if($gallery->count() > 1)
              <div class="product-thumbnails" role="group" aria-label="{{ __('content.product.gallery') }}">
                @foreach($gallery as $index => $media)
                  <button
                    type="button"
                    class="product-thumbnail{{ $index === 0 ? ' is-active' : '' }}"
                    aria-label="{{ __('content.product.show_image', ['number' => $index + 1]) }}"
                    aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                    data-gallery-thumb
                    data-src="{{ $media['fallback'] }}"
                    data-srcset="{{ $media['srcset'] }}"
                    data-avif-srcset="{{ $media['avif_srcset'] }}"
                    data-width="{{ $media['width'] }}"
                    data-height="{{ $media['height'] }}"
                    data-alt="{{ __('content.product.image_alt', ['name' => $name, 'number' => $index + 1]) }}"
                  ><img src="{{ $media['src'] }}" width="72" height="72" alt="" loading="lazy" decoding="async"></button>
                @endforeach
              </div>
            @endif
          </div>

          <div class="product-summary">
            <p class="content-eyebrow">{{ $type }}</p>
            <h1 id="product-title">{{ $name }}</h1>
            @if($shortDescription)<p class="product-lead">{{ $shortDescription }}</p>@endif

            @if($benefits)
              <ul class="product-benefits" aria-label="{{ __('content.product.key_benefits') }}">
                @foreach($benefits as $benefit)<li>{{ $benefit }}</li>@endforeach
              </ul>
            @endif

            @if($formValue || $packageValue)
              <dl class="product-quick-facts">
                @if($formValue)<div><dt>{{ __('content.product.form') }}</dt><dd>{{ $formValue }}</dd></div>@endif
                @if($packageValue)<div><dt>{{ __('content.product.package') }}</dt><dd>{{ $packageValue }}</dd></div>@endif
              </dl>
            @endif

            @if($salesMode === 'direct')
              <div class="product-commerce">
                @if($priceLabel)<strong class="product-price">{{ $priceLabel }}</strong>@endif
                @if($product->stock_status)
                  <span class="product-availability product-availability--{{ $product->stock_status }}"><span aria-hidden="true"></span>{{ $availabilityLabels[$product->stock_status] ?? $product->stock_status }}</span>
                @endif
              </div>
            @endif

            <div class="product-actions">
              <a class="primary-action product-primary-cta" href="{{ $ctaHref }}" @if($ctaExternal) rel="nofollow sponsored noopener" target="_blank" @endif>
                {{ $ctaLabel }} <span aria-hidden="true">→</span>
              </a>
            </div>
            <p class="medical-disclaimer"><span class="product-disclaimer-icon" aria-hidden="true">i</span><span>{{ $disclaimer }}</span></p>
          </div>
        </section>

        <section class="product-trust-panel" aria-label="{{ __('content.product.trust_title') }}">
          <dl>
            <div><dt>{{ __('content.product.manufacturer') }}</dt><dd>{{ $manufacturer }}</dd></div>
            <div><dt>{{ __('content.product.country') }}</dt><dd>{{ $country }}</dd></div>
            @if($registration)<div><dt>{{ __('content.product.registration') }}</dt><dd>{{ \App\Support\Content::plain($registration) }}</dd></div>@endif
            @if($product->instruction_file)<div><dt>{{ __('content.product.instruction_label') }}</dt><dd><a href="{{ media_url($product->instruction_file) }}" download>{{ __('content.product.instruction') }}</a></dd></div>@endif
          </dl>
        </section>

        <div class="product-content-stack">
          @if($descriptionHtml || $benefits)
            <section class="product-info-section" aria-labelledby="product-overview-title">
              <h2 id="product-overview-title">{{ __('content.product.overview') }}</h2>
              @if($descriptionHtml)<div class="rich-content">{!! $descriptionHtml !!}</div>@endif
              @if(!$descriptionHtml && $benefits)<ul class="product-scan-list">@foreach($benefits as $benefit)<li>{{ $benefit }}</li>@endforeach</ul>@endif
            </section>
          @endif
          @if($compositionHtml)
            <section class="product-info-section" aria-labelledby="product-composition-title"><h2 id="product-composition-title">{{ __('content.product.composition') }}</h2><div class="rich-content">{!! $compositionHtml !!}</div></section>
          @endif
          @if($applicationHtml)
            <section class="product-info-section" aria-labelledby="product-directions-title"><h2 id="product-directions-title">{{ __('content.product.directions') }}</h2><div class="rich-content">{!! $applicationHtml !!}</div></section>
          @endif
          @if($warningsHtml)
            <section class="product-info-section product-info-section--warning" aria-labelledby="product-warnings-title"><h2 id="product-warnings-title">{{ __('content.product.warnings') }}</h2><div class="rich-content">{!! $warningsHtml !!}</div></section>
          @endif
          @if($storageHtml || $shelfLife)
            <section class="product-info-section" aria-labelledby="product-storage-title">
              <h2 id="product-storage-title">{{ __('content.product.storage_and_shelf') }}</h2>
              <dl class="product-definition-list">
                @if($storageHtml)<div><dt>{{ __('content.product.storage') }}</dt><dd class="rich-content">{!! $storageHtml !!}</dd></div>@endif
                @if($shelfLife)<div><dt>{{ __('content.product.shelf_life') }}</dt><dd>{{ $shelfLife }}</dd></div>@endif
              </dl>
            </section>
          @endif
          @if($salesMode === 'direct')
            <section class="product-info-section" aria-labelledby="product-delivery-title">
              <h2 id="product-delivery-title">{{ __('content.product.delivery_returns') }}</h2>
              <div class="product-delivery-grid">
                <div><h3>{{ __('content.product.delivery') }}</h3><p>{{ __('content.product.delivery_text') }}</p></div>
                <div><h3>{{ __('content.product.returns') }}</h3><p>{{ __('content.product.returns_text') }}</p></div>
              </div>
            </section>
          @endif
        </div>

        @if($faqs)
          <section class="product-faq" aria-labelledby="product-faq-title">
            <h2 id="product-faq-title">{{ __('content.product.faq') }}</h2>
            <div class="product-faq__list">
              @foreach($faqs as $faq)
                <details><summary>{{ $faq['question'] }}<span aria-hidden="true">+</span></summary><div>{!! \App\Support\Content::sanitize($faq['answer']) !!}</div></details>
              @endforeach
            </div>
          </section>
        @endif
      </article>

      @if(($relatedProducts ?? collect())->isNotEmpty())
        <section class="related-section" aria-labelledby="related-title">
          <p class="content-eyebrow">{{ __('content.product.continue_exploring') }}</p>
          <h2 id="related-title">{{ __('content.product.related') }}</h2>
          <div class="nl-products-grid">
            @foreach($relatedProducts as $relatedProduct)<x-product-card :product="$relatedProduct" />@endforeach
          </div>
        </section>
      @endif
    </div>

    <aside class="product-sticky-cta" aria-label="{{ __('content.product.mobile_action') }}">
      @if($priceLabel)<span class="product-sticky-cta__price">{{ $priceLabel }}</span>@endif
      <a class="primary-action" href="{{ $ctaHref }}" @if($ctaExternal) rel="nofollow sponsored noopener" target="_blank" @endif>{{ $ctaLabel }}</a>
    </aside>
  </main>

  @push('scripts')
    <script>
      window.dataLayer=window.dataLayer||[];window.dataLayer.push({event:'product_view',product_id:@json((string)$product->id),product_name:@json($name),sales_mode:@json($salesMode),page_path:window.location.pathname});
      (function(){
        var gallery=document.querySelector('[data-product-gallery]');
        if(gallery){
          var image=gallery.querySelector('[data-gallery-image]');
          var webp=gallery.querySelector('[data-gallery-webp]');
          var avif=gallery.querySelector('[data-gallery-avif]');
          gallery.querySelectorAll('[data-gallery-thumb]').forEach(function(button){
            button.addEventListener('click',function(){
              image.src=button.dataset.src;
              image.srcset=button.dataset.srcset||'';
              image.width=button.dataset.width;
              image.height=button.dataset.height;
              image.alt=button.dataset.alt;
              if(webp) webp.srcset=button.dataset.srcset||'';
              if(avif) avif.srcset=button.dataset.avifSrcset||'';
              gallery.querySelectorAll('[data-gallery-thumb]').forEach(function(item){item.classList.remove('is-active');item.setAttribute('aria-pressed','false')});
              button.classList.add('is-active');button.setAttribute('aria-pressed','true');
            });
          });
        }
        var sticky=document.querySelector('.product-sticky-cta');
        if(sticky && 'ResizeObserver' in window){
          new ResizeObserver(function(){
            document.documentElement.style.setProperty('--product-sticky-height',sticky.offsetHeight+'px');
          }).observe(sticky);
          var reserveChatSpace=function(){
            if(document.getElementById('jivo-iframe-container')){
              document.documentElement.style.setProperty('--product-chat-reserve','52px');
              return true;
            }
            return false;
          };
          if(!reserveChatSpace()){
            var chatObserver=new MutationObserver(function(){
              if(reserveChatSpace()) chatObserver.disconnect();
            });
            chatObserver.observe(document.body,{childList:true,subtree:true});
          }
        }
      })();
    </script>
  @endpush
</x-layouts.index>
