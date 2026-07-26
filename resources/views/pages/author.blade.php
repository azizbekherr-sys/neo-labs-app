@php
  $locale = in_array(app()->getLocale(), ['uz', 'ru', 'en'], true) ? app()->getLocale() : 'ru';
  $name = __('content.author.title');
  $role = \App\Support\Content::localized($author, 'author_role', $locale) ?: __('content.author.role_fallback');
  $canonical = \App\Support\Seo::canonical();
  $schema = \App\Support\Seo::clean([
    '@type' => 'Person',
    '@id' => $canonical . '#person',
    'name' => $name,
    'jobTitle' => $role,
    'url' => $canonical,
    'worksFor' => ['@id' => \App\Support\Seo::baseUrl() . '/#organization'],
  ]);
@endphp
<x-layouts.index :title="$name" :description="__('content.author.bio')" :schema="$schema">
  @push('head')<link rel="stylesheet" href="{{ asset('css/content-pages.css') }}">@endpush
  <main class="content-page author-page">
    <div class="container article-shell">
      <section class="author-profile" aria-labelledby="author-title">
        <div class="author-mark" aria-hidden="true">NL</div>
        <div>
          <p class="content-eyebrow">{{ __('content.author.about') }}</p>
          <h1 id="author-title">{{ $name }}</h1>
          <p class="author-role">{{ $role }}</p>
          <p>{{ __('content.author.bio') }}</p>
          <a class="secondary-action" href="{{ route('editorial-policy') }}">{{ __('content.author.policy') }}</a>
        </div>
      </section>
      <section class="related-section" aria-labelledby="author-articles">
        <h2 id="author-articles">{{ __('content.author.articles') }}</h2>
        <div class="editorial-grid">
          @foreach($articles as $article)<x-article-card :article="$article" />@endforeach
        </div>
      </section>
    </div>
  </main>
</x-layouts.index>
