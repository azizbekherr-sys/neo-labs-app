@php
  $paginationPrev = $products->previousPageUrl();
  $paginationNext = $products->nextPageUrl();
@endphp
<x-layouts.index
  :title="__('content.catalog.title')"
  :description="__('content.catalog.description')"
  :prev="$paginationPrev"
  :next="$paginationNext"
  image="/img/neo-labs-products.webp"
  preload-image="/img/neo-labs-products.webp"
>
  @push('head')
    <link rel="stylesheet" href="{{ asset('css/content-pages.css') }}">
  @endpush

  <main class="content-page">
    <section class="content-hero content-hero--catalog" aria-labelledby="catalog-title">
      <div class="container content-hero__inner">
        <p class="content-eyebrow">{{ __('content.catalog.eyebrow') }}</p>
        <h1 id="catalog-title">{{ __('content.catalog.title') }}</h1>
        <p>{{ __('content.catalog.intro') }}</p>
      </div>
    </section>

    <section class="content-section" aria-label="{{ __('content.catalog.title') }}">
      <div class="container">
        <form class="search-bar" method="GET" action="{{ route('catalog') }}" role="search">
          <div class="field-group">
            <label for="catalog-search">{{ __('content.catalog.search_label') }}</label>
            <input id="catalog-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('content.catalog.search_placeholder') }}" autocomplete="off">
          </div>
          <button class="primary-action" type="submit">{{ __('content.catalog.search_button') }}</button>
          @if(request()->filled('q'))
            <a class="secondary-action" href="{{ route('catalog') }}">{{ __('content.catalog.clear') }}</a>
          @endif
        </form>

        <div class="nl-products-grid">
          @forelse($products as $product)
            <x-product-card :product="$product" heading-level="h2" />
          @empty
            <p class="empty-state">{{ __('content.catalog.empty') }}</p>
          @endforelse
        </div>

        @if($products->hasPages())
          <nav class="pagination-wrap" aria-label="Pagination">{{ $products->links() }}</nav>
        @endif
      </div>
    </section>
  </main>
</x-layouts.index>
