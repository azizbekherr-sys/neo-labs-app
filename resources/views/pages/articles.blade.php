<x-layouts.index
  :title="__('content.article.index_title')"
  :description="__('content.article.index_description')"
  :prev="$articles->previousPageUrl()"
  :next="$articles->nextPageUrl()"
  image="/img/neo-labs-products.webp"
  preload-image="/img/neo-labs-products.webp"
>
  @push('head')
    <link rel="stylesheet" href="{{ asset('css/content-pages.css') }}">
  @endpush

  <main class="content-page">
    <section class="content-hero" aria-labelledby="news-title">
      <div class="container content-hero__inner">
        <p class="content-eyebrow">{{ __('content.article.eyebrow') }}</p>
        <h1 id="news-title">{{ __('content.article.index_title') }}</h1>
        <p>{{ __('content.article.index_description') }}</p>
      </div>
    </section>

    <section class="content-section" aria-label="{{ __('content.article.index_title') }}">
      <div class="container">
        <form class="search-bar" method="GET" action="{{ route('articles') }}" role="search">
          <div class="field-group">
            <label for="article-search">{{ __('content.article.search_label') }}</label>
            <input id="article-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('content.article.search_placeholder') }}" autocomplete="off">
          </div>
          <button class="primary-action" type="submit">{{ __('content.article.search_button') }}</button>
          @if(request()->filled('q'))
            <a class="secondary-action" href="{{ route('articles') }}">{{ __('content.article.clear') }}</a>
          @endif
        </form>

        <div class="editorial-grid">
          @forelse($articles as $article)
            <x-article-card :article="$article" />
          @empty
            <p class="empty-state">{{ __('content.article.empty') }}</p>
          @endforelse
        </div>

        @if($articles->hasPages())
          <nav class="pagination-wrap" aria-label="Pagination">{{ $articles->links() }}</nav>
        @endif
      </div>
    </section>
  </main>
</x-layouts.index>
