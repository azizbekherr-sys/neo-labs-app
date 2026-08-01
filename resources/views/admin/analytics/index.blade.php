@extends('admin.layouts.app')
@section('title', 'Sayt analitikasi')

@php
  $summary = $report['summary'];
  $channelLabels = [
    'direct' => 'To‘g‘ridan-to‘g‘ri', 'organic' => 'Organik qidiruv', 'paid' => 'Pullik reklama',
    'social' => 'Ijtimoiy tarmoq', 'ai' => 'AI / GEO manbasi', 'email' => 'Email',
    'referral' => 'Boshqa saytdan', 'campaign' => 'Kampaniya',
  ];
  $pageTypeLabels = [
    'home' => 'Bosh sahifa', 'catalog' => 'Katalog', 'product' => 'Mahsulot', 'articles' => 'Maqolalar',
    'article' => 'Maqola', 'manufacturing' => 'Ishlab chiqarish', 'contacts' => 'Aloqa', 'about' => 'Biz haqimizda',
    'certificates' => 'Sertifikatlar', 'certificate' => 'Sertifikat', 'production' => 'Ishlab chiqarish', 'company_facts' => 'Kompaniya faktlari',
    'policy' => 'Siyosat', 'page' => 'Boshqa sahifa',
  ];
  $deviceLabels = ['desktop' => 'Kompyuter', 'mobile' => 'Telefon', 'tablet' => 'Planshet'];
  $eventLabels = [
    'phone_click' => 'Telefon bosildi', 'email_click' => 'Email bosildi', 'social_click' => 'Ijtimoiy havola',
    'contact_form_submit' => 'Forma yuborildi', 'outbound_click' => 'Tashqi havola',
  ];
  $trendMax = max(1, collect($report['trend'])->max('views') ?: 1);
  $trendStep = max(1, (int) ceil(max(1, count($report['trend'])) / 8));
  $products = collect($report['top_content'])->where('page_type', 'product')->take(8);
  $articles = collect($report['top_content'])->where('page_type', 'article')->take(8);
@endphp

@section('content')
  <header class="admin-page-header analytics-page-header">
    <div>
      <span class="admin-eyebrow"><i class="bi bi-bar-chart-line" aria-hidden="true"></i>First-party analytics</span>
      <h1 class="h3 mb-1">Sayt analitikasi</h1>
      <p>Kim, qayerdan va qaysi qurilma orqali kirayotgani, sahifa hamda kontent ko‘rishlari.</p>
    </div>
    <div class="analytics-header-actions">
      <form class="analytics-period-form" method="GET" action="{{ route('dashboard.analytics') }}">
        <label for="analytics-days">Davr</label>
        <select class="form-select" id="analytics-days" name="days" onchange="this.form.submit()">
          @foreach($periods as $period)
            <option value="{{ $period }}" {{ $report['days'] === $period ? 'selected' : '' }}>Oxirgi {{ $period }} kun</option>
          @endforeach
        </select>
        <noscript><button class="btn btn-primary" type="submit">Ko‘rsatish</button></noscript>
      </form>
      <a class="btn btn-outline-primary" href="{{ route('dashboard.analytics.export', ['days' => $report['days']]) }}">
        <i class="bi bi-download me-2" aria-hidden="true"></i>CSV eksport
      </a>
    </div>
  </header>

  <section class="analytics-metrics" aria-labelledby="analytics-summary-title">
    <h2 class="visually-hidden" id="analytics-summary-title">Asosiy ko‘rsatkichlar</h2>
    <article class="analytics-metric analytics-metric--primary"><span class="analytics-metric__icon"><i class="bi bi-eye"></i></span><div><span>Ko‘rishlar</span><strong>{{ number_format($summary['page_views'], 0, '.', ' ') }}</strong><small>{{ $report['days'] }} kun ichida</small></div></article>
    <article class="analytics-metric analytics-metric--success"><span class="analytics-metric__icon"><i class="bi bi-people"></i></span><div><span>Unikal tashrifchi</span><strong>{{ number_format($summary['visitors'], 0, '.', ' ') }}</strong><small>Anonim visitor ID bo‘yicha</small></div></article>
    <article class="analytics-metric analytics-metric--info"><span class="analytics-metric__icon"><i class="bi bi-window-stack"></i></span><div><span>Seanslar</span><strong>{{ number_format($summary['sessions'], 0, '.', ' ') }}</strong><small>Brauzer seanslari</small></div></article>
    <article class="analytics-metric"><span class="analytics-metric__icon"><i class="bi bi-files"></i></span><div><span>Sahifa / seans</span><strong>{{ number_format($summary['pages_per_session'], 2) }}</strong><small>O‘rtacha chuqurlik</small></div></article>
    <article class="analytics-metric analytics-metric--warning"><span class="analytics-metric__icon"><i class="bi bi-box-arrow-right"></i></span><div><span>Bir sahifali seans</span><strong>{{ number_format($summary['bounce_rate'], 1) }}%</strong><small>Faqat 1 sahifa ko‘rganlar</small></div></article>
  </section>

  <section class="admin-card analytics-chart-card" aria-labelledby="analytics-trend-title">
    <div class="admin-card-header analytics-card-heading">
      <div><h2 class="h5 mb-1" id="analytics-trend-title">Ko‘rishlar dinamikasi</h2><p>Kunlik sahifa ko‘rishlari va unikal tashrifchilar.</p></div>
      <div class="analytics-legend"><span><i class="analytics-dot analytics-dot--views"></i>Ko‘rishlar</span><span><i class="analytics-dot analytics-dot--visitors"></i>Tashrifchilar</span></div>
    </div>
    <div class="admin-card-body">
      @if(count($report['trend']))
        <div class="analytics-chart-scroll">
          <div class="analytics-chart" style="--analytics-points:{{ count($report['trend']) }}" role="img" aria-label="Oxirgi {{ $report['days'] }} kundagi ko‘rishlar grafigi">
            @foreach($report['trend'] as $index => $point)
              @php
                $viewHeight = $point['views'] ? max(5, round(($point['views'] / $trendMax) * 100, 2)) : 0;
                $visitorHeight = $point['visitors'] ? max(4, round(($point['visitors'] / $trendMax) * 100, 2)) : 0;
              @endphp
              <div class="analytics-chart__point" title="{{ \Carbon\Carbon::parse($point['day'])->format('d.m.Y') }} — {{ $point['views'] }} ko‘rish, {{ $point['visitors'] }} tashrifchi">
                <div class="analytics-chart__bars"><i class="analytics-chart__bar analytics-chart__bar--views" style="height:{{ $viewHeight }}%"></i><i class="analytics-chart__bar analytics-chart__bar--visitors" style="height:{{ $visitorHeight }}%"></i></div>
                @if($index % $trendStep === 0 || $loop->last)<span>{{ \Carbon\Carbon::parse($point['day'])->format('d.m') }}</span>@else<span aria-hidden="true">&nbsp;</span>@endif
              </div>
            @endforeach
          </div>
        </div>
      @else
        <x-admin.empty-state icon="bar-chart-line" title="Hali analitika yo‘q" text="Yangi tashriflar yig‘ilgach grafik shu yerda ko‘rinadi." />
      @endif
    </div>
  </section>

  <div class="row g-4 mt-0">
    <section class="col-12 col-xxl-7" aria-labelledby="top-pages-title">
      <div class="admin-card h-100">
        <div class="admin-card-header analytics-card-heading"><div><h2 class="h5 mb-1" id="top-pages-title">Eng ko‘p ko‘rilgan sahifalar</h2><p>Ko‘rishlar va unikal auditoriya bo‘yicha.</p></div></div>
        @if(count($report['top_pages']))
          <div class="table-responsive"><table class="table admin-table analytics-table mb-0"><thead><tr><th>Sahifa</th><th>Turi</th><th class="text-end">Ko‘rish</th><th class="text-end">Visitor</th></tr></thead><tbody>
          @foreach($report['top_pages'] as $page)<tr><td><a href="{{ url($page['path']) }}" target="_blank" rel="noopener"><strong>{{ \Illuminate\Support\Str::limit(preg_replace('/\s+[—|-]\s+NEO-LABS.*$/u', '', $page['title'] ?: $page['path']), 62) }}</strong><small>{{ $page['path'] }}</small></a></td><td><span class="analytics-type-badge">{{ $pageTypeLabels[$page['page_type']] ?? $page['page_type'] }}</span></td><td class="text-end fw-bold">{{ number_format($page['views'], 0, '.', ' ') }}</td><td class="text-end text-muted">{{ number_format($page['visitors'], 0, '.', ' ') }}</td></tr>@endforeach
          </tbody></table></div>
        @else
          <div class="admin-card-body"><x-admin.empty-state icon="file-earmark-bar-graph" title="Sahifa ko‘rishlari yo‘q" /></div>
        @endif
      </div>
    </section>

    <section class="col-12 col-xxl-5" aria-labelledby="channels-title">
      <div class="admin-card h-100">
        <div class="admin-card-header analytics-card-heading"><div><h2 class="h5 mb-1" id="channels-title">Kirish kanallari</h2><p>Foydalanuvchi saytni qayerdan topdi.</p></div></div>
        <div class="admin-card-body analytics-distribution-list">
          @php $channelMax = max(1, collect($report['channels'])->max('total') ?: 1); @endphp
          @forelse($report['channels'] as $channel)
            <div class="analytics-distribution"><div><span>{{ $channelLabels[$channel['label']] ?? ucfirst($channel['label'] ?: 'Noma’lum') }}</span><strong>{{ number_format($channel['total'], 0, '.', ' ') }}</strong></div><div class="analytics-progress"><i style="width:{{ round(($channel['total'] / $channelMax) * 100, 2) }}%"></i></div><small>{{ number_format($channel['visitors'], 0, '.', ' ') }} unikal visitor</small></div>
          @empty<x-admin.empty-state icon="signpost-split" title="Kanal ma’lumoti yo‘q" />@endforelse
        </div>
      </div>
    </section>
  </div>

  <div class="row g-4 mt-0">
    <section class="col-12 col-xl-6" aria-labelledby="top-products-title">
      <div class="admin-card h-100"><div class="admin-card-header analytics-card-heading"><div><h2 class="h5 mb-1" id="top-products-title">Top mahsulotlar</h2><p>Mahsulot detail sahifasi ko‘rishlari.</p></div></div><div class="admin-card-body analytics-ranked-list">
        @forelse($products as $item)<a href="{{ url($item['path']) }}" target="_blank" rel="noopener"><span class="analytics-rank">{{ $loop->iteration }}</span><span><strong>{{ \Illuminate\Support\Str::limit(preg_replace('/\s+[—|-]\s+NEO-LABS.*$/u', '', $item['title'] ?: $item['path']), 58) }}</strong><small>{{ $item['visitors'] }} unikal visitor</small></span><b>{{ number_format($item['views'], 0, '.', ' ') }}</b></a>@empty<x-admin.empty-state icon="box-seam" title="Mahsulot ko‘rishlari yo‘q" />@endforelse
      </div></div>
    </section>
    <section class="col-12 col-xl-6" aria-labelledby="top-articles-title">
      <div class="admin-card h-100"><div class="admin-card-header analytics-card-heading"><div><h2 class="h5 mb-1" id="top-articles-title">Top maqolalar</h2><p>Maqola o‘qish sahifasi ko‘rishlari.</p></div></div><div class="admin-card-body analytics-ranked-list">
        @forelse($articles as $item)<a href="{{ url($item['path']) }}" target="_blank" rel="noopener"><span class="analytics-rank">{{ $loop->iteration }}</span><span><strong>{{ \Illuminate\Support\Str::limit(preg_replace('/\s+[—|-]\s+NEO-LABS.*$/u', '', $item['title'] ?: $item['path']), 58) }}</strong><small>{{ $item['visitors'] }} unikal visitor</small></span><b>{{ number_format($item['views'], 0, '.', ' ') }}</b></a>@empty<x-admin.empty-state icon="newspaper" title="Maqola ko‘rishlari yo‘q" />@endforelse
      </div></div>
    </section>
  </div>

  <div class="analytics-insight-grid mt-4">
    @foreach([
      ['title'=>'Qurilmalar','icon'=>'phone','items'=>$report['devices'],'labels'=>$deviceLabels],
      ['title'=>'Brauzerlar','icon'=>'browser-chrome','items'=>$report['browsers'],'labels'=>[]],
      ['title'=>'Operatsion tizim','icon'=>'pc-display','items'=>$report['systems'],'labels'=>[]],
      ['title'=>'Mamlakatlar','icon'=>'geo-alt','items'=>$report['countries'],'labels'=>[]],
      ['title'=>'Sayt tillari','icon'=>'translate','items'=>$report['locales'],'labels'=>['uz'=>'O‘zbekcha','ru'=>'Русский','en'=>'English']],
      ['title'=>'Vaqt zonalari','icon'=>'clock-history','items'=>$report['timezones'],'labels'=>[]],
    ] as $block)
      <section class="admin-card analytics-insight" aria-label="{{ $block['title'] }}">
        <div class="analytics-insight__title"><span><i class="bi bi-{{ $block['icon'] }}"></i></span><h2>{{ $block['title'] }}</h2></div>
        @php $blockMax = max(1, collect($block['items'])->max('total') ?: 1); @endphp
        <div class="analytics-mini-list">@forelse($block['items'] as $item)<div><span title="{{ $item['label'] ?: 'Aniqlanmagan' }}">{{ $block['labels'][$item['label']] ?? ($item['label'] ?: 'Aniqlanmagan') }}</span><i><b style="width:{{ round(($item['total'] / $blockMax) * 100, 2) }}%"></b></i><strong>{{ $item['total'] }}</strong></div>@empty<p>Ma’lumot yo‘q</p>@endforelse</div>
      </section>
    @endforeach
  </div>

  <div class="row g-4 mt-0">
    <section class="col-12 col-xl-7" aria-labelledby="referrers-title"><div class="admin-card h-100"><div class="admin-card-header analytics-card-heading"><div><h2 class="h5 mb-1" id="referrers-title">Top referrer saytlar</h2><p>Qaysi domenlardan tashrif kelgan.</p></div></div><div class="admin-card-body analytics-referrer-grid">@forelse($report['referrers'] as $referrer)<div><span class="analytics-domain-icon"><i class="bi bi-globe2"></i></span><span><strong>{{ $referrer['label'] }}</strong><small>{{ $referrer['visitors'] }} visitor</small></span><b>{{ $referrer['total'] }}</b></div>@empty<x-admin.empty-state icon="globe2" title="Referrer ma’lumoti yo‘q" />@endforelse</div></div></section>
    <section class="col-12 col-xl-5" aria-labelledby="events-title"><div class="admin-card h-100"><div class="admin-card-header analytics-card-heading"><div><h2 class="h5 mb-1" id="events-title">Muhim harakatlar</h2><p>Aloqa va tashqi havola bosishlari.</p></div></div><div class="admin-card-body analytics-event-list">@forelse($report['events'] as $event)<div><span><i class="bi bi-lightning-charge"></i>{{ $eventLabels[$event['event_type']] ?? $event['event_type'] }}</span><strong>{{ number_format($event['total'], 0, '.', ' ') }}</strong></div>@empty<x-admin.empty-state icon="cursor" title="Harakatlar hali yo‘q" />@endforelse</div></div></section>
  </div>

  <section class="admin-card mt-4" aria-labelledby="recent-visits-title">
    <div class="admin-card-header analytics-card-heading"><div><h2 class="h5 mb-1" id="recent-visits-title">Oxirgi tashriflar</h2><p>Shaxsiy ma’lumotlarsiz so‘nggi sahifa ko‘rishlari.</p></div><span class="analytics-privacy-note"><i class="bi bi-shield-check"></i>IP anonim hash ko‘rinishida</span></div>
    @if(count($report['recent']))<div class="table-responsive"><table class="table admin-table analytics-table analytics-recent-table mb-0"><thead><tr><th>Vaqt</th><th>Sahifa</th><th>Kanal</th><th>Qurilma</th><th>Joylashuv</th><th>Til</th></tr></thead><tbody>
      @foreach($report['recent'] as $visit)<tr><td><time datetime="{{ $visit['occurred_at'] }}">{{ \Carbon\Carbon::parse($visit['occurred_at'])->format('d.m H:i') }}</time></td><td><strong>{{ \Illuminate\Support\Str::limit(preg_replace('/\s+[—|-]\s+NEO-LABS.*$/u', '', $visit['title'] ?: $visit['path']), 52) }}</strong><small>{{ $visit['path'] }}</small></td><td><span class="analytics-channel">{{ $channelLabels[$visit['channel']] ?? $visit['channel'] }}</span>@if($visit['source'])<small>{{ $visit['source'] }}</small>@endif</td><td><strong>{{ $deviceLabels[$visit['device_type']] ?? $visit['device_type'] }}</strong><small>{{ $visit['browser'] }} · {{ $visit['operating_system'] }}</small></td><td><strong>{{ $visit['country_code'] ?: 'Aniqlanmagan' }}</strong><small>{{ $visit['city'] ?: $visit['timezone'] }}</small></td><td><span class="analytics-locale">{{ strtoupper($visit['locale'] ?: '—') }}</span></td></tr>@endforeach
    </tbody></table></div>@else<div class="admin-card-body"><x-admin.empty-state icon="activity" title="Tashriflar hali yo‘q" text="Public sahifalar ochilgach ma’lumotlar avtomatik yig‘iladi." /></div>@endif
  </section>

  <p class="analytics-footnote"><i class="bi bi-info-circle" aria-hidden="true"></i>Mamlakat va shahar proxy/CDN geo-header yuborganda aniqlanadi. Foydalanuvchining ochiq IP manzili saqlanmaydi va bot trafik hisobga olinmaydi.</p>
@endsection
