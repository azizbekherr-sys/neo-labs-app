@php
  $title = __('site.editorial.title');
  $description = __('site.editorial.description');
@endphp
<x-layouts.index :title="$title" :description="$description">
  <main style="background:#fff;padding:36px 0 48px;">
    <article class="container" style="max-width:900px;line-height:1.8;">
      <h1>{{ $title }}</h1>
      <p>{{ $description }}</p>
      <h2>{{ __('site.editorial.preparation_title') }}</h2>
      <p>{{ __('site.editorial.preparation_text') }}</p>
      <h2>{{ __('site.editorial.sources_title') }}</h2>
      <p>{{ __('site.editorial.sources_text') }}</p>
      <h2>{{ __('site.editorial.review_title') }}</h2>
      <p>{{ __('site.editorial.review_text') }}</p>
      <h2>{{ __('site.editorial.corrections_title') }}</h2>
      <p>{{ __('site.editorial.corrections_text') }}</p>
      <h2>{{ __('site.editorial.advertising_title') }}</h2>
      <p>{{ __('site.editorial.advertising_text') }}</p>
      <aside style="margin-top:24px;padding:16px;border-radius:14px;background:#f8fafc;color:#475569;">
        {{ __('site.editorial.disclaimer') }}
      </aside>
    </article>
  </main>
</x-layouts.index>
