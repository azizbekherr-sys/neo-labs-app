@php
  $locale = in_array(app()->getLocale(), ['uz', 'ru', 'en'], true) ? app()->getLocale() : 'ru';
  $title = trim((string) (\App\Support\Content::localized($article, 'title', $locale) ?: 'NEO-LABS'));
  $body = \App\Support\Content::sanitize(\App\Support\Content::localized($article, 'description', $locale));
  $metaDescription = \App\Support\Content::localized($article, 'meta_description', $locale) ?: \App\Support\Content::excerpt($body, 170);
  $media = \App\Support\Media::responsive($article->photo, 'articles', [480, 960]);
  $slug = \Illuminate\Support\Str::slug($title ?: 'news-' . $article->id);
  $autoCanonical = \App\Support\Seo::route('articles.show', ['locale' => $locale, 'article' => $article, 'slug' => $slug]);
  $customCanonicalPath = $article->canonical_url ? parse_url($article->canonical_url, PHP_URL_PATH) : null;
  $canonical = ($customCanonicalPath && \Illuminate\Support\Str::startsWith($customCanonicalPath, '/' . $locale . '/')) ? $article->canonical_url : $autoCanonical;
  $authorRole = \App\Support\Content::localized($article, 'author_role', $locale);
  $reviewerRole = \App\Support\Content::localized($article, 'reviewer_role', $locale);
  $references = $article->{'references_' . $locale} ?: [];
  $keywordList = $article->{'keywords_' . $locale} ?: [];
  $metaKeywords = $keywordList ? implode(', ', $keywordList) : null;
  $ogTitle = \App\Support\Content::localized($article, 'og_title', $locale);
  $ogDescription = \App\Support\Content::localized($article, 'og_description', $locale);
  $imageAlt = trim((string) \App\Support\Content::localized($article, 'image_alt', $locale)) ?: $title;
  $publishedYear = optional($article->created_at)->year;
  $isArchive = $publishedYear && $publishedYear < now()->year;
  $authorUrl = $article->author_slug ? route('authors.show', ['slug' => $article->author_slug]) : null;
  $schema = \App\Support\Seo::clean([
    '@type' => in_array($article->schema_type, ['Article', 'BlogPosting', 'MedicalWebPage'], true) ? $article->schema_type : 'BlogPosting',
    '@id' => $canonical . '#article',
    'headline' => $title,
    'image' => [$media['src']],
    'datePublished' => optional($article->created_at)->toAtomString(),
    'dateModified' => optional($article->updated_at)->toAtomString(),
    'inLanguage' => $locale . '-UZ',
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
    'author' => $article->author_name ? ['@type' => 'Person', 'name' => $article->author_name, 'jobTitle' => $authorRole, 'url' => $authorUrl] : null,
    'reviewedBy' => ($article->reviewer_name && $article->reviewed_at) ? ['@type' => 'Person', 'name' => $article->reviewer_name, 'jobTitle' => $reviewerRole] : null,
    'publisher' => ['@id' => \App\Support\Seo::baseUrl() . '/#organization'],
    'description' => \App\Support\Content::localized($article, 'schema_description', $locale) ?: $metaDescription,
    'citation' => $references,
  ]);
  $breadcrumbs = [
    ['name' => __('site.common.home'), 'url' => '/' . $locale],
    ['name' => __('content.article.index_title'), 'url' => '/' . $locale . '/news'],
    ['name' => $title, 'url' => $canonical],
  ];
@endphp

<x-layouts.index
  :title="\App\Support\Content::localized($article, 'seo_title', $locale) ?: $title"
  :description="$metaDescription"
  :image="$article->og_image ?: $media['src']"
  :canonical="$canonical"
  :robots="$article->robots"
  :schema="$schema"
  :breadcrumbs="$breadcrumbs"
  :keywords="$metaKeywords"
  :og-title="$ogTitle"
  :og-description="$ogDescription"
  :image-alt="$imageAlt"
  og-type="article"
  :preload-image="$media['src']"
>
  @push('head')
    <link rel="stylesheet" href="{{ asset('css/content-pages.css') }}?v={{ filemtime(public_path('css/content-pages.css')) }}">
  @endpush

  <main class="content-page article-detail">
    <div class="container article-shell">
      <article>
        <header class="article-header">
          @if($isArchive)<p class="archive-label">{{ __('content.article.archive', ['year' => $publishedYear]) }}</p>@endif
          <h1>{{ $title }}</h1>
          <dl class="article-byline">
            <div><dt>{{ __('content.article.published') }}</dt><dd><time datetime="{{ optional($article->created_at)->toDateString() }}">{{ optional($article->created_at)->locale($locale)->translatedFormat('d M Y') }}</time></dd></div>
            @if($article->updated_at && !$article->updated_at->equalTo($article->created_at))
              <div><dt>{{ __('content.article.updated') }}</dt><dd><time datetime="{{ $article->updated_at->toDateString() }}">{{ $article->updated_at->locale($locale)->translatedFormat('d M Y') }}</time></dd></div>
            @endif
            @if($article->author_name)
              <div><dt>{{ __('content.article.author') }}</dt><dd>@if($authorUrl)<a href="{{ $authorUrl }}">{{ $article->author_name }}</a>@else{{ $article->author_name }}@endif @if($authorRole)<span>— {{ $authorRole }}</span>@endif</dd></div>
            @endif
            @if($article->reviewer_name && $article->reviewed_at)
              <div><dt>{{ __('content.article.reviewer') }}</dt><dd>{{ $article->reviewer_name }} @if($reviewerRole)<span>— {{ $reviewerRole }}</span>@endif</dd></div>
            @endif
          </dl>
          @if($article->photo)
            <div class="article-cover"><img src="{{ $media['src'] }}" @if($media['srcset']) srcset="{{ $media['srcset'] }}" sizes="(min-width:880px) 840px, 100vw" @endif width="{{ $media['width'] }}" height="{{ $media['height'] }}" alt="{{ $imageAlt }}" loading="eager" fetchpriority="high" decoding="async"></div>
          @endif
        </header>

        <div class="article-notice">{{ __('content.article.notice') }}</div>
        <div class="article-body rich-content">
          @if(trim($body) !== '')
            {!! $body !!}
          @else
            <p>{{ $locale === 'uz' ? 'Maqola matni vaqtincha mavjud emas.' : ($locale === 'en' ? 'The article content is temporarily unavailable.' : 'Содержание статьи временно недоступно.') }}</p>
          @endif
        </div>

        @if(is_array($references) && count($references))
          <section class="article-references" aria-labelledby="references-title">
            <h2 id="references-title">{{ __('content.article.references') }}</h2>
            <ol>
              @foreach($references as $reference)
                @php
                  $referenceLabel = is_array($reference) ? ($reference['title'] ?? $reference['url'] ?? '') : (string) $reference;
                  $referenceUrl = is_array($reference) ? ($reference['url'] ?? null) : (filter_var($reference, FILTER_VALIDATE_URL) ? $reference : null);
                @endphp
                @if($referenceLabel)
                  <li>@if($referenceUrl)<a href="{{ $referenceUrl }}" rel="noopener noreferrer" target="_blank">{{ $referenceLabel }}</a>@else{{ $referenceLabel }}@endif</li>
                @endif
              @endforeach
            </ol>
          </section>
        @endif
      </article>
    </div>
  </main>
</x-layouts.index>
