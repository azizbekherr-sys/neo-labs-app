@props(['items' => [], 'title' => null])
@if(count($items))
  <section class="seo-faq" aria-labelledby="faq-heading" style="padding:32px 0;">
    <div class="container">
      <h2 id="faq-heading" style="margin:0 0 18px;color:#18361d;">{{ $title ?: __('site.common.faq_title') }}</h2>
      <div style="display:grid;gap:12px;">
        @foreach($items as $item)
          <details style="border:1px solid #dceedd;border-radius:14px;padding:14px 16px;background:#fff;">
            <summary style="cursor:pointer;font-weight:700;color:#18361d;">{{ $item['question'] }}</summary>
            <div style="padding-top:10px;line-height:1.7;color:#475569;">{!! nl2br(e($item['answer'])) !!}</div>
          </details>
        @endforeach
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
