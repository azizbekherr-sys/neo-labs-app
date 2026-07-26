@props(['article', 'headingLevel' => 'h2'])
@php
  $locale = in_array(app()->getLocale(), ['uz', 'ru', 'en'], true) ? app()->getLocale() : 'ru';
  $headingLevel = in_array($headingLevel, ['h2', 'h3', 'h4'], true) ? $headingLevel : 'h2';
  $title = trim((string) (\App\Support\Content::localized($article, 'title', $locale) ?: 'NEO-LABS'));
  $excerpt = \App\Support\Content::excerpt(\App\Support\Content::localized($article, 'description', $locale), 180);
  $media = \App\Support\Media::responsive($article->photo, 'articles', [480, 960]);
  $url = route('articles.show', ['article' => $article, 'slug' => \Illuminate\Support\Str::slug($title ?: 'news-' . $article->id)]);
  $date = optional($article->created_at)->locale($locale)->translatedFormat('d M Y');
@endphp
<article {{ $attributes->class(['editorial-card']) }}>
  <a class="editorial-card__link" href="{{ $url }}">
    <div class="editorial-card__media">
      <img src="{{ $media['src'] }}" @if($media['srcset']) srcset="{{ $media['srcset'] }}" sizes="(min-width:900px) 50vw, 100vw" @endif width="{{ $media['width'] }}" height="{{ $media['height'] }}" alt="{{ $title }}" loading="lazy" decoding="async">
    </div>
    <div class="editorial-card__body">
      <div class="editorial-card__meta">
        @if($date)<time datetime="{{ optional($article->created_at)->toDateString() }}">{{ $date }}</time>@endif
        <span>{{ number_format((int) $article->views, 0, '.', ' ') }} {{ mb_strtolower(__('content.article.views')) }}</span>
      </div>
      <{{ $headingLevel }} class="editorial-card__title">{{ $title }}</{{ $headingLevel }}>
      <p class="editorial-card__excerpt">{{ $excerpt }}</p>
      <span class="editorial-card__action">{{ __('content.article.read') }} <span aria-hidden="true">→</span></span>
    </div>
  </a>
</article>
