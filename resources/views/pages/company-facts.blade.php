@php
  $title = __('site.facts.title');
  $description = __('site.facts.description');
  $core = [
    [__('site.facts.name'), config('seo.site_name')],
    [__('site.facts.legal_name'), config('seo.legal_name')],
    [__('site.facts.founded'), '25.06.2020'],
    [__('site.facts.location'), __('site.facts.location_value')],
    [__('site.facts.activity'), __('site.facts.activity_value')],
    [__('site.facts.formats'), __('site.facts.formats_value')],
    [__('site.facts.capacity'), __('site.facts.capacity_value')],
    [__('site.facts.services'), __('site.facts.services_value')],
    [__('site.facts.phone'), config('seo.phone')],
    ['Email', config('seo.email')],
  ];
@endphp
<x-layouts.index :title="$title" :description="$description">
  <main style="background:#fff;padding:36px 0 48px;">
    <div class="container">
      <h1>{{ $title }}</h1>
      <p style="max-width:850px;line-height:1.7;color:#64748b;">{{ $description }}</p>
      <dl style="display:grid;grid-template-columns:minmax(180px,280px) 1fr;gap:0;margin-top:24px;border:1px solid #dceedd;border-radius:16px;overflow:hidden;">
        @foreach($core as [$label, $value])
          <dt style="padding:14px;background:#f3ffe9;border-bottom:1px solid #dceedd;">{{ $label }}</dt>
          <dd style="padding:14px;margin:0;border-bottom:1px solid #dceedd;">{{ $value }}</dd>
        @endforeach
        @foreach($facts as $fact)
          @php $factLabel = $fact->{'label_' . app()->getLocale()} ?: $fact->label_ru; $factValue = $fact->{'value_' . app()->getLocale()} ?: $fact->value_ru; @endphp
          <dt style="padding:14px;background:#f3ffe9;border-bottom:1px solid #dceedd;">{{ $factLabel }}</dt>
          <dd style="padding:14px;margin:0;border-bottom:1px solid #dceedd;">
            {{ $factValue }}
            @if($fact->source_url)<br><a href="{{ $fact->source_url }}" rel="noopener noreferrer">{{ __('site.facts.source') }}</a>@endif
            @if($fact->document_path)<br><a href="{{ asset('storage/' . $fact->document_path) }}">{{ __('site.facts.document') }}</a>@endif
          </dd>
        @endforeach
      </dl>
      @if(count(config('seo.social_profiles', [])))
        <h2>{{ __('site.facts.official_pages') }}</h2>
        <ul>
          @foreach(config('seo.social_profiles') as $profile)
            <li><a data-analytics="social_click" href="{{ $profile }}" rel="me noopener noreferrer">{{ parse_url($profile, PHP_URL_HOST) }}</a></li>
          @endforeach
        </ul>
      @endif
      <p style="margin-top:18px;color:#64748b;">{{ __('site.facts.certificate_notice') }}</p>
    </div>
  </main>
</x-layouts.index>
