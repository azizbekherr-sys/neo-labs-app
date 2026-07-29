@php
  $title = __('site.privacy.title');
  $description = __('site.privacy.description');
@endphp
<x-layouts.index :title="$title" :description="$description">
  @push('head')<link rel="stylesheet" href="{{ asset('css/content-pages.css') }}?v={{ filemtime(public_path('css/content-pages.css')) }}">@endpush
  <main class="content-page">
    <article class="container policy-page">
      <p class="content-eyebrow">NEO-LABS</p>
      <h1>{{ $title }}</h1>
      <p class="policy-lead">{{ __('site.privacy.intro') }}</p>

      <section aria-labelledby="privacy-data">
        <h2 id="privacy-data">{{ __('site.privacy.data_title') }}</h2>
        <p>{{ __('site.privacy.data_text') }}</p>
      </section>
      <section aria-labelledby="privacy-use">
        <h2 id="privacy-use">{{ __('site.privacy.use_title') }}</h2>
        <p>{{ __('site.privacy.use_text') }}</p>
      </section>
      <section aria-labelledby="privacy-processors">
        <h2 id="privacy-processors">{{ __('site.privacy.processors_title') }}</h2>
        <p>{{ __('site.privacy.processors_text') }}</p>
      </section>
      <section aria-labelledby="privacy-cookies">
        <h2 id="privacy-cookies">{{ __('site.privacy.cookies_title') }}</h2>
        <p>{{ __('site.privacy.cookies_text') }}</p>
      </section>
      <section aria-labelledby="privacy-retention">
        <h2 id="privacy-retention">{{ __('site.privacy.retention_title') }}</h2>
        <p>{{ __('site.privacy.retention_text') }}</p>
      </section>
      <section aria-labelledby="privacy-rights">
        <h2 id="privacy-rights">{{ __('site.privacy.rights_title') }}</h2>
        <p>{{ __('site.privacy.rights_text') }}</p>
      </section>
      <section aria-labelledby="privacy-contact">
        <h2 id="privacy-contact">{{ __('site.privacy.contact_title') }}</h2>
        <p>{{ __('site.privacy.contact_text') }} <a href="mailto:{{ config('seo.email') }}">{{ config('seo.email') }}</a>.</p>
      </section>

      <p class="policy-updated">{{ __('site.privacy.updated') }}</p>
    </article>
  </main>
</x-layouts.index>
