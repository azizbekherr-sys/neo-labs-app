@php
  $title = __('site.certificates.title');
  $description = __('site.certificates.description');
@endphp
<x-layouts.index :title="$title" :description="$description">
  <main style="background:#fff;padding:36px 0 48px;">
    <div class="container">
      <h1 style="margin:0 0 12px;">{{ $title }}</h1>
      <p style="max-width:800px;color:#64748b;line-height:1.7;">{{ $description }}</p>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;margin-top:24px;">
        @forelse($certificates as $certificate)
          @php
            $name = $certificate->{'name_' . app()->getLocale()} ?: $certificate->name_ru;
            $issuer = $certificate->{'issuer_' . app()->getLocale()} ?: $certificate->issuer_ru;
            $scope = $certificate->{'scope_' . app()->getLocale()} ?: $certificate->scope_ru;
          @endphp
          <article id="certificate-{{ $certificate->id }}" style="border:1px solid #dceedd;border-radius:18px;padding:20px;box-shadow:0 10px 30px rgba(17,94,50,.06);">
            <h2 style="font-size:1.2rem;margin:0 0 12px;">{{ $name }}</h2>
            <dl style="display:grid;grid-template-columns:max-content 1fr;gap:8px 14px;margin:0;">
              @if($certificate->number)<dt>{{ __('site.certificates.number') }}</dt><dd>{{ $certificate->number }}</dd>@endif
              @if($issuer)<dt>{{ __('site.certificates.issuer') }}</dt><dd>{{ $issuer }}</dd>@endif
              @if($certificate->issued_at)<dt>{{ __('site.certificates.issued_at') }}</dt><dd>{{ $certificate->issued_at->format('d.m.Y') }}</dd>@endif
              @if($certificate->expires_at)<dt>{{ __('site.certificates.expires_at') }}</dt><dd>{{ $certificate->expires_at->format('d.m.Y') }}</dd>@endif
              @if($scope)<dt>{{ __('site.certificates.scope') }}</dt><dd>{{ $scope }}</dd>@endif
            </dl>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;">
              <a href="{{ route('certificates.show', ['certificate' => $certificate, 'slug' => \Illuminate\Support\Str::slug($name)]) }}">{{ __('site.certificates.details') }}</a>
              @if($certificate->verification_url)<a href="{{ $certificate->verification_url }}" rel="noopener noreferrer">{{ __('site.certificates.verify') }}</a>@endif
            </div>
          </article>
        @empty
          <p style="grid-column:1/-1;padding:18px;background:#f8fafc;border-radius:14px;color:#64748b;">
            {{ __('site.certificates.empty') }}
          </p>
        @endforelse
      </div>
    </div>
  </main>
</x-layouts.index>
