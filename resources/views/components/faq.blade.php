@props(['items' => [], 'title' => null])
@if(count($items))
  <section class="seo-faq" aria-labelledby="faq-heading">
    <div class="container seo-faq__container">
      <div class="seo-faq__panel">
        <header class="seo-faq__header">
          <span class="seo-faq__eyebrow" aria-hidden="true">NEO-LABS</span>
          <h2 id="faq-heading">{{ $title ?: __('site.common.faq_title') }}</h2>
        </header>
        <div class="seo-faq__list">
        @foreach($items as $item)
          <details class="seo-faq__item">
            <summary>
              <span class="seo-faq__question">{{ $item['question'] }}</span>
              <span class="seo-faq__icon" aria-hidden="true">+</span>
            </summary>
            <div class="seo-faq__answer">{!! nl2br(e($item['answer'])) !!}</div>
          </details>
        @endforeach
        </div>
      </div>
    </div>
  </section>
  @push('schema')
    <script type="application/ld+json">{!! json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'FAQPage',
      'mainEntity' => array_map(function ($item) {
        return [
          '@type' => 'Question',
          'name' => $item['question'],
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
        ];
      }, $items),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
  @endpush
@endif
