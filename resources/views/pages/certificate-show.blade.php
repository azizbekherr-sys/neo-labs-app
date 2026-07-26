@php
  $name = $certificate->{'name_' . app()->getLocale()} ?: $certificate->name_ru;
  $issuer = $certificate->{'issuer_' . app()->getLocale()} ?: $certificate->issuer_ru;
  $scope = $certificate->{'scope_' . app()->getLocale()} ?: $certificate->scope_ru;
  $schema = \App\Support\Seo::clean([
    '@type' => 'CreativeWork',
    '@id' => \App\Support\Seo::canonical() . '#certificate',
    'name' => $name,
    'identifier' => $certificate->number,
    'dateCreated' => optional($certificate->issued_at)->toDateString(),
    'expires' => optional($certificate->expires_at)->toDateString(),
    'creator' => $issuer ? ['@type' => 'Organization', 'name' => $issuer] : null,
    'about' => $scope,
    'url' => $certificate->verification_url,
  ]);
@endphp
<x-layouts.index :title="$name" :description="$scope ?: $name" :schema="$schema">
  <main style="padding:36px 0 48px;background:#fff;">
    <article class="container" style="max-width:900px;">
      <a href="{{ route('certificates') }}">← {{ __('site.common.certificates') }}</a>
      <h1>{{ $name }}</h1>
      @if($scope)<p style="line-height:1.7;">{{ $scope }}</p>@endif
      <ul style="line-height:1.9;">
        @if($certificate->number)<li><strong>{{ __('site.certificates.number_colon') }}</strong> {{ $certificate->number }}</li>@endif
        @if($issuer)<li><strong>{{ __('site.certificates.issuer_colon') }}</strong> {{ $issuer }}</li>@endif
        @if($certificate->issued_at)<li><strong>{{ __('site.certificates.issued_colon') }}</strong> {{ $certificate->issued_at->format('d.m.Y') }}</li>@endif
        @if($certificate->expires_at)<li><strong>{{ __('site.certificates.expires_colon') }}</strong> {{ $certificate->expires_at->format('d.m.Y') }}</li>@endif
      </ul>
      @if($certificate->document_path)<a data-analytics="certificate_download" href="{{ media_url($certificate->document_path) }}" download>{{ __('site.certificates.download') }}</a>@endif
      @if($certificate->verification_url)<p><a href="{{ $certificate->verification_url }}" rel="noopener noreferrer">{{ __('site.certificates.verify_official') }}</a></p>@endif
    </article>
  </main>
</x-layouts.index>
