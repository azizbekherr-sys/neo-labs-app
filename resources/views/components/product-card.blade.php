@props(['product', 'headingLevel' => 'h3'])

@php
  $localeRaw = app()->getLocale();
  $locale = in_array($localeRaw, ['ru', 'uz', 'en'], true) ? $localeRaw : 'ru';
  $headingLevel = in_array($headingLevel, ['h2', 'h3', 'h4'], true) ? $headingLevel : 'h3';
  $name = trim((string) (\App\Support\Content::localized($product, 'name', $locale) ?? ('product-' . $product->id)));
  $benefit = trim((string) (\App\Support\Content::localized($product, 'type', $locale) ?? \App\Support\Content::localized($product, 'form', $locale) ?? 'NEO-LABS'));
  $description = \App\Support\Content::excerpt(
      \App\Support\Content::localized($product, 'short_description', $locale)
          ?: \App\Support\Content::localized($product, 'description', $locale)
          ?: \App\Support\Content::localized($product, 'composition', $locale),
      190
  );
  $description = $description !== '' ? $description : __('content.product.fallback');
  $disclaimer = trim((string) (\App\Support\Content::localized($product, 'disclaimer', $locale) ?: __('content.product.disclaimer')));

  $primaryImage = is_array($product->images) && count($product->images)
      ? $product->images[0]
      : ($product->image ?? null);
  $media = \App\Support\Media::responsive(is_string($primaryImage) ? $primaryImage : null, 'products', [320, 640]);
  $hasImage = is_string($primaryImage)
      && trim($primaryImage) !== ''
      && $media['src'] !== asset('img/placeholder.png');

  $url = route('product.show', [
      'product' => $product,
      'slug' => \Illuminate\Support\Str::slug($name),
  ]);
@endphp

<article {{ $attributes->class(['nl-product-card']) }}>
  <a class="nl-product-card__link" href="{{ $url }}" aria-label="{{ __('content.product.open', ['name' => $name]) }}">
  <div class="nl-product-card__media{{ $hasImage ? '' : ' is-fallback' }}">
    <span class="nl-product-card__fallback" aria-hidden="true">
      <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="9" y="22" width="46" height="20" rx="10" stroke="currentColor" stroke-width="3"/>
        <path d="M32 22V42" stroke="currentColor" stroke-width="3"/>
        <path d="M16 32H27" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
      </svg>
      <span>NEO-LABS</span>
    </span>
    @if($hasImage)
      <img
        class="nl-product-card__image"
        src="{{ $media['src'] }}"
        @if($media['srcset']) srcset="{{ $media['srcset'] }}" sizes="(min-width:1280px) 25vw, (min-width:768px) 50vw, 100vw" @endif
        alt="{{ $name }}"
        width="{{ $media['width'] }}"
        height="{{ $media['height'] }}"
        loading="lazy"
        decoding="async"
        onerror="this.hidden=true;this.closest('.nl-product-card__media').classList.add('is-fallback')"
      />
    @endif
  </div>

  <div class="nl-product-card__content">
    <p class="nl-product-card__benefit">{{ $benefit }}</p>
    <{{ $headingLevel }} class="nl-product-card__title">{{ $name }}</{{ $headingLevel }}>
    <p class="nl-product-card__description">{{ $description }}</p>
    <span class="nl-product-card__disclaimer">{{ $disclaimer }}</span>
    <span class="nl-product-card__button">
      <span>{{ __('content.product.details') }}</span>
      <span class="nl-product-card__arrow" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M4.16675 10H15.8334M15.8334 10L10.8334 5M15.8334 10L10.8334 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
    </span>
  </div>
  </a>
</article>
