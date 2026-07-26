@php
  $title = __('site.production.title');
  $description = __('site.production.description');
  $faq = __('site.production.faq');
@endphp
<x-layouts.index :title="$title" :description="$description" image="/img/neo-labs-contract-manufacturing.webp" preload-image="/img/neo-labs-contract-manufacturing.webp">
  <main style="background:#fff;padding:36px 0 20px;">
    <div class="container">
      <h1>{{ $title }}</h1>
      <p style="max-width:900px;line-height:1.8;">{{ $description }}</p>
      <h2>{{ __('site.production.capacity_title') }}</h2>
      <ul style="line-height:1.9;">
        @foreach(__('site.production.capacities') as $capacity)<li>{{ $capacity }}</li>@endforeach
      </ul>
      <h2>{{ __('site.production.lines_title') }}</h2>
      <p style="line-height:1.8;">{{ __('site.production.lines_text') }}</p>
      <h2>{{ __('site.production.storage_title') }}</h2>
      <p style="line-height:1.8;">{{ __('site.production.storage_text') }}</p>
      <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin:24px 0;">
        <img src="{{ asset('img/neo-labs-production-1.webp') }}" alt="{{ __('site.about.production_site_alt') }}" width="1200" height="628" loading="lazy" decoding="async" style="width:100%;height:auto;border-radius:16px;">
        <img src="{{ asset('img/neo-labs-production-3.webp') }}" alt="{{ __('site.about.packaging_line_alt') }}" width="1090" height="628" loading="lazy" decoding="async" style="width:100%;height:auto;border-radius:16px;">
      </div>
      <h2>{{ __('site.production.process_title') }}</h2>
      <ol style="line-height:1.9;">
        @foreach(__('site.production.process') as $step)<li>{{ $step }}</li>@endforeach
      </ol>
      <p><a data-analytics="contract_request" href="{{ route('contacts') }}">{{ __('site.production.request') }}</a></p>
      <aside style="padding:14px 16px;background:#f8fafc;border-radius:12px;color:#475569;">{{ __('site.production.notice') }}</aside>
    </div>
  </main>
  <x-faq :items="$faq" />
</x-layouts.index>
