<x-layouts.index image="/img/neo-labs-og.jpg" preload-image="/img/untitled.png">

    <main style="background:#ffffff;">
      <section id="hero" class="hero">
        <div class="container hero-grid">
          <div class="hero-content">
            <h1>
              <span>{{ __('site.home.heading_start') }}</span>
              <span class="accent">{{ __('site.home.heading_accent') }}</span>
              <span>{{ __('site.home.heading_end') }}</span>
            </h1>
            <p>
              {{ __('site.home.description') }}
            </p>
            <a class="btn" href="{{ route('catalog') }}">{{ __('Вся продукция') }}</a>
          </div>
          <div class="hero-image">
            <img src="img/untitled.png" width="865" height="870" alt="{{ __('site.home.image_alt') }}" loading="eager" fetchpriority="high" decoding="async" />
          </div>
        </div>
      </section>

      <section class="partners">
        <div class="container">
          <div class="partners-carousel" id="partnersCarousel">
            <button class="partners-btn prev" type="button" aria-label="{{ __('Назад') }}">‹</button>
            <div class="partners-viewport">
              <div class="partners-track">
                @if(!empty($partners))
                  @foreach($partners as $partner)
                    @php
                      $partnerPath = is_object($partner) ? $partner->path : $partner;
                      $partnerFile = basename((string) $partnerPath);
                      $partnerNames = [
                        'pr_69280b6e953d2_1764232046.PNG' => 'LifePrime',
                        'pr_69281ccb176a6_1764236491.png' => 'Evert Pharma AG',
                        'pr_69281cf134421_1764236529.jpg' => 'Family Calm',
                        'pr_693c2d6df0981_1765551469.jpg' => 'Biofit',
                      ];
                      $optimizedPartner = 'partners/optimized/' . pathinfo($partnerFile, PATHINFO_FILENAME) . '.webp';
                      $src = media_url(is_file(public_path($optimizedPartner)) ? $optimizedPartner : $partnerPath);
                      $partnerName = $partnerNames[$partnerFile] ?? '';
                      $plink = is_object($partner) ? ($partner->url ?? null) : null;
                    @endphp
                    @if($plink)
                      <a href="{{ $plink }}" target="_blank" rel="noopener">
                        <img src="{{ $src }}" width="360" height="360" alt="{{ $partnerName ? $partnerName . ' logo' : '' }}" loading="lazy" decoding="async" />
                      </a>
                    @else
                      <img src="{{ $src }}" width="360" height="360" alt="{{ $partnerName ? $partnerName . ' logo' : '' }}" loading="lazy" decoding="async" />
                    @endif
                  @endforeach
                @else
                  <img src="img/Group 1171275060.png" alt="{{ __('Family Calm') }}" />
                  <img src="img/Logo (1).png" alt="{{ __('Neo-labs бренд') }}" />
                  <img src="img/logos.png" alt="{{ __('Nik Pharm') }}" />
                  <img src="img/Group 1171275060.png" alt="{{ __('Family Calm премиум') }}" />
                @endif
              </div>
            </div>
            <button class="partners-btn next" type="button" aria-label="{{ __('Вперёд') }}">›</button>
          </div>
          <style>
            /* Partners carousel + grayscale to color on hover */
            .partners-carousel{ position:relative; }
            .partners-viewport{ overflow:hidden; display:flex; justify-content:center; }
            .partners-track{
              display:inline-flex; align-items:center; gap:24px; padding:6px 4px;
              transition: transform .3s ease;
              will-change: transform;
            }
            .partners-track img{
              width: clamp(120px, 16vw, 160px);
              height: auto;
              filter: grayscale(100%);
              opacity: .65;
              transition: filter .3s ease, opacity .3s ease, transform .25s ease;
              user-select: none;
              pointer-events: auto;
            }
            .partners-track img:hover{ filter: none; opacity: 1; transform: translateY(-2px); }
            .partners-btn{
              position:absolute; top:50%; transform: translateY(-50%);
              width:44px; height:44px; border-radius:50%;
              border:1px solid rgba(0,0,0,.08); background:#fff;
              box-shadow: 0 6px 14px rgba(0,0,0,.08);
              display:none; align-items:center; justify-content:center;
              font-size:22px; line-height:1; color:#18361d; cursor:pointer;
            }
            .partners-btn.prev{ left:-6px; }
            .partners-btn.next{ right:-6px; }
            .partners-btn.show{ display:flex; }
            @media (max-width: 720px){
              .partners-btn.prev{ left:2px; } .partners-btn.next{ right:2px; }
            }
          </style>
          <script>
            (function () {
              var root = document.getElementById('partnersCarousel');
              if (!root) return;
              var viewport = root.querySelector('.partners-viewport');
              var track = root.querySelector('.partners-track');
              var prev = root.querySelector('.partners-btn.prev');
              var next = root.querySelector('.partners-btn.next');
              if (!viewport || !track || !prev || !next) return;
              function updateButtons(){
                // show buttons only if content overflows
                var overflow = track.scrollWidth > viewport.clientWidth + 8;
                prev.classList.toggle('show', overflow);
                next.classList.toggle('show', overflow);
                if (!overflow) return;
                // enable/disable ends
                var maxScroll = track.scrollWidth - viewport.clientWidth;
                var left = viewport.scrollLeft;
                prev.disabled = left <= 4;
                next.disabled = left >= maxScroll - 4;
              }
              function scrollByDir(dir){
                var delta = Math.round(viewport.clientWidth * 0.8);
                viewport.scrollBy({ left: dir * delta, behavior:'smooth' });
              }
              prev.addEventListener('click', function(){ scrollByDir(-1); });
              next.addEventListener('click', function(){ scrollByDir(1); });
              viewport.addEventListener('scroll', updateButtons);
              window.addEventListener('resize', updateButtons);
              // Make viewport scrollable container
              viewport.style.overflowX = 'auto';
              viewport.style.scrollBehavior = 'smooth';
              viewport.style.scrollbarWidth = 'none';
              viewport.style.msOverflowStyle = 'none';
              viewport.addEventListener('wheel', function(e){
                if (Math.abs(e.deltaX) < Math.abs(e.deltaY)) {
                  viewport.scrollLeft += e.deltaY;
                  e.preventDefault();
                }
              }, { passive:false });
              // hide native scrollbar (webkit)
              var style = document.createElement('style');
              style.textContent = '.partners-viewport::-webkit-scrollbar{ display:none; }';
              document.head.appendChild(style);
              updateButtons();
            })();
          </script>
        </div>
      </section>

      <section id="manufacturing" class="stats" style="padding:32px 0;">
        <div class="container" style="max-width:1320px;">
          <div style="background:#eaf9e8;border-radius:28px;padding:36px 28px;">
            <div class="section-head" style="text-align:center;margin-bottom:24px;">
              <h2 style="margin:8px 0 0 0;color: #5FBB46;font-weight:900;">{{ __('Годовые объёмы производства') }}</h2>
            </div>
            <style>
              @media (max-width: 992px) {
                #manufacturing .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; gap:16px !important; }
                #manufacturing .stats-grid img { max-width:260px !important; }
              }
              @media (max-width: 576px) {
                #manufacturing .stats-grid { grid-template-columns: 1fr !important; gap:14px !important; }
                #manufacturing .stats-grid img { max-width:220px !important; }
              }
            </style>
            <style>
              #manufacturing .mf-grid{ display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:28px; align-items:center; }
              #manufacturing .mf-item{ display:flex; flex-direction:row; align-items:center; justify-content:center; gap:14px; }
              #manufacturing .mf-icon{ width:96px; height:96px; border-radius:999px; background:#fff; display:flex; align-items:center; justify-content:center; box-shadow:0 10px 24px rgba(17,94,50,.10); flex:0 0 auto; }
              #manufacturing .mf-icon img{ width:56px; height:56px; object-fit:contain; }
              #manufacturing .mf-text{ display:flex; flex-direction:column; align-items:flex-start; font-family:'Raleway','Inter',sans-serif; }
              #manufacturing .mf-num{ font-weight:700; color:#5FBB46; font-size:clamp(1.8rem,3.2vw,3.2rem); line-height:1; display:flex; align-items:baseline; gap:6px; }
              #manufacturing .mf-num span{ font-size:clamp(0.95rem,1.4vw,1.4rem); font-weight:700; color:#5FBB46; }
              #manufacturing .mf-label{ color:#5FBB46; font-weight:700; font-size:clamp(0.95rem,1.2vw,1.15rem); margin-top:6px; }
              @media (max-width: 992px){ #manufacturing .mf-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); gap:22px; } }
              @media (max-width: 576px){ #manufacturing .mf-grid{ grid-template-columns:1fr; } }
            </style>
            <div class="mf-grid">
              <div class="mf-item">
                <div class="mf-icon"><img src="{{ asset('img/de1.png') }}" alt="tablets"></div>
                <div class="mf-text">
                  <div class="mf-num">100 <span>{{ __('млн') }}</span></div>
                  <div class="mf-label">{{ __('Таблетки') }}</div>
                </div>
              </div>
              <div class="mf-item">
                <div class="mf-icon"><img src="{{ asset('img/de4.png') }}" alt="capsules"></div>
                <div class="mf-text">
                  <div class="mf-num">65 <span>{{ __('млн') }}</span></div>
                  <div class="mf-label">{{ __('Капсулы') }}</div>
                </div>
              </div>
              <div class="mf-item">
                <div class="mf-icon"><img src="{{ asset('img/de3.png') }}" alt="vials"></div>
                <div class="mf-text">
                  <div class="mf-num">30 <span>{{ __('млн') }}</span></div>
                  <div class="mf-label">{{ __('Флаконы') }}</div>
                </div>
              </div>
              <div class="mf-item">
                <div class="mf-icon"><img src="{{ asset('img/de2.png') }}" alt="packs"></div>
                <div class="mf-text">
                  <div class="mf-num">4,5 <span>{{ __('млн') }}</span></div>
                  <div class="mf-label">{{ __('Пакеты') }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="about" class="treatments">
        <div class="container">
          <div class="section-head">
            <h2 class="treatments-title">{{ __('site.home.directions_title') }}</h2>
            <p class="treatments-sub">{{ __('site.home.directions_text') }}</p>
          </div>
          <style>
            /* Exact heading style for Treatments section */
            #about.treatments .section-head .treatments-title{
              font-size: clamp(2.2rem, 3.4vw, 3rem);
              margin: 0 0 6px 0;
              font-weight: 900;
              letter-spacing: .01em;
              background: linear-gradient(120deg, #228C3F 0%, #89D033 100%);
              -webkit-background-clip: text;
              background-clip: text;
              color: transparent;
              text-align: center;
              /* remove green glow, use subtle black shadow */
              text-shadow: 0 2px 8px rgba(0,0,0,.15);
            }
            #about.treatments .section-head .treatments-sub{
              margin: 0;
              text-align: center;
              font-size: 0.98rem;
              color: #2f9f44;
              opacity: .9;
            }
            /* Center text inside treatment cards like the mock */
            #about.treatments .card-row .treatment-card{
              align-items: center !important;
              justify-content: center !important;
              text-align: center;
            }
            #about.treatments .card-row .treatment-card span{
              font-weight: 800;
              font-size: clamp(1.1rem, 1.6vw, 1.4rem);
            }
            /* Make treatment cards fully transparent */
            #about.treatments .card-row .treatment-card{
              background: transparent !important;
              background-color: transparent !important;
            }
            #about.treatments .card-row .treatment-card::after{
              background: transparent !important;
            }
          </style>
          <div class="card-row">
            <div class="treatment-card">
              <img src="img/pre1.png" alt="{{ __('Почки и мочевыводящие пути') }}" />
              <span>{{ __('Почки и мочевыводящие пути') }}</span>
            </div>
            <div class="treatment-card">
              <img src="img/pre2.png" alt="{{ __('Головной мозг') }}" />
              <span>{{ __('Головной мозг') }}</span>
            </div>
            <div class="treatment-card">
              <img src="img/pre3.png" alt="{{ __('Дефицит железа') }}" />
              <span>{{ __('Дефицит железа') }}</span>
            </div>
            <div class="treatment-card">
              <img src="img/pre5.png" alt="{{ __('Желудок') }}" />
              <span>{{ __('Желудок') }}</span>
            </div>
          </div>
        </div>
      </section>

      <section id="products" class="products products-recommended" style="background:#ffffff;">
        <div class="container">
          <div class="products-panel">
            <div class="section-head products-head">
              <span class="products-caption">{{ __('Рекомендуемые товары') }}</span>
              <h2 class="products-title">{{ __('Наши продукты') }}</h2>
            </div>
            <style>
              /* Panel background and heading styles */
              #products .products-panel{
                background: linear-gradient(135deg,#F1FFE9 0%, #E9FBE7 100%);
                border:1px solid #E3F4E5;
                border-radius: 28px;
                padding: 26px 24px 30px;
              }
              #products .products-head{
                text-align:center;
                margin-bottom: 18px;
              }
              #products .products-caption{
                display:inline-block;
                color:#64B95C;
                font-weight:600;
                font-size:.9rem;
                margin-bottom: 6px;
              }
              #products .products-title{
                margin:0;
                font-weight:900;
                color:#5FBB46;
                letter-spacing:.01em;
                font-size: clamp(1.8rem, 3vw, 2.2rem);
              }
            </style>
            <div class="nl-products-grid">
            @if(isset($products) && $products->count())
              @foreach($products as $product)
                <x-product-card :product="$product" />
              @endforeach
            @else
              <div style="grid-column:1/-1;color:#6B7280;">{{ __('Пока нет товаров.') }}</div>
            @endif
            </div>
            <div style="display:flex;justify-content:center;margin-top:22px;"><a class="btn" href="{{ route('catalog') }}" style="background:#347d3d;color:#fff;border-radius:14px;padding:13px 20px;text-decoration:none;">{{ __('Вся продукция') }}</a></div>
          </div>
        </div>
      </section>

      <section id="articles" class="articles">
        <div class="container">
          <div class="articles-panel">
            <div class="section-head articles-head">
              <span class="articles-caption">{{ __('Полезные советы и новости') }}</span>
              <h2 class="articles-title">{{ __('Новые публикации о медицине') }}<br>{{ __('и здоровье') }}</h2>
            </div>
            <style>
              /* Panel and headings */
              #articles .articles-panel{
                background: linear-gradient(135deg,#F1FFE9 0%, #E9FBE7 100%);
                border:1px solid #E3F4E5;
                border-radius: 28px;
                padding: 26px 24px 30px;
              }
              #articles .articles-head{ text-align:center; margin-bottom: 18px; }
              #articles .articles-caption{
                display:inline-block; color:#64B95C; font-weight:600; font-size:.9rem; margin-bottom:6px;
              }
              #articles .articles-title{
                margin:0; font-weight:900; letter-spacing:.01em;
                background: linear-gradient(120deg, #228C3F 0%, #89D033 100%);
                -webkit-background-clip:text; background-clip:text; color:transparent;
                text-shadow: 0 2px 8px rgba(0,0,0,.08);
                font-size: clamp(1.8rem, 3vw, 2.4rem);
              }
              /* Grid with horizontal cards */
              #articles .articles-grid{ display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; }
              #articles .article-card{
                display:grid; grid-template-columns: 240px 1fr; gap:20px; align-items:center;
                background:#fff; border:1px solid #E6F4E8; border-radius:16px; padding:16px 18px;
                box-shadow:0 12px 28px rgba(17,94,50,0.06);
              }
              #articles .article-card img{
                width:100%; height:180px; object-fit:cover; border-radius:14px; grid-column:1; grid-row:1/4;
              }
              #articles .article-card h3{ margin:0 0 6px 0; font-size:1.1rem; }
              #articles .article-card p{ margin:0 0 10px 0; color:#6B7280; }
              #articles .article-card a{
                color:#5FBB46; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px;
              }
              #articles .article-card a::after{ content:"→"; display:inline-block; }
              /* Creative polish for cards */
              #articles .article-card{
                transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
                position: relative;
                overflow: hidden;
              }
              #articles .article-card::after{
                content:""; position:absolute; inset:auto -20% 0 -20%; height:38%; border-radius:50%;
                background: radial-gradient(80% 70% at 50% 100%, rgba(105,206,121,.08), transparent 70%);
                pointer-events:none;
              }
              #articles .article-card:hover{
                transform: translateY(-2px);
                box-shadow: 0 16px 40px rgba(17,94,50,0.10);
                border-color: #DBF3E2;
              }
              #articles .article-card img{
                transition: transform .35s ease, filter .35s ease;
                will-change: transform;
              }
              #articles .article-card:hover img{ transform: scale(1.06); }
              /* Make the "Подробнее" look like a pill button */
              #articles .article-card a{
                background: linear-gradient(135deg,#89D033 0%, #5FBB46 100%);
                color:#fff; padding:10px 14px; border-radius:999px;
                box-shadow: 0 10px 24px rgba(34,197,94,0.18);
                justify-self: start; /* prevent stretching in CSS Grid */
                width: auto; white-space: nowrap; display: inline-flex;
              }
              #articles .article-card a::after{ content:""; }
              #articles .article-card a span.icon{
                display:inline-block; transform: translateX(0); transition: transform .2s ease;
              }
              #articles .article-card a:hover span.icon{ transform: translateX(2px); }
              /* Article modal */
              #articles .am-backdrop{
                position: fixed; inset: 0; background: rgba(0,0,0,.4);
                backdrop-filter: blur(4px);
                display: none; align-items: center; justify-content: center;
                z-index: 3000; padding: 20px;
              }
              #articles .am-backdrop.show{ display:flex; animation: amFade .18s ease-out; }
              #articles .am-modal{
                width: min(920px, 96vw); border-radius: 22px;
                background: rgba(255,255,255,.96);
                border: 1px solid #E3F4E5;
                box-shadow: 0 30px 80px rgba(17,94,50,0.22), 0 8px 24px rgba(0,0,0,.06);
                overflow: hidden;
                transform: translateY(8px) scale(.98);
                opacity: .98;
                animation: amPop .22s ease-out forwards;
                position: relative;
              }
              #articles .am-hero{
                position: relative; height: clamp(200px, 36vw, 320px); overflow: hidden;
                background: linear-gradient(135deg,#F1FFE9 0%, #E9FBE7 100%);
              }
              #articles .am-hero img{
                width: 100%; height: 100%; object-fit: cover;
                filter: saturate(1.06) contrast(1.03);
                transform: scale(1.02);
                animation: amHeroZoom 12s ease-in-out infinite alternate;
              }
              #articles .am-hero::after{
                content:""; position:absolute; inset:0;
                background: linear-gradient(180deg, rgba(0,0,0,0) 40%, rgba(0,0,0,.18) 100%);
              }
              #articles .am-body{
                padding: 18px 20px 22px 20px;
                max-height: min(52vh, 460px);
                overflow: auto;
              }
              #articles .am-title{
                margin: 0 0 10px 0; font-weight: 900; letter-spacing: .01em;
                background: linear-gradient(120deg, #228C3F 0%, #89D033 100%);
                -webkit-background-clip:text; background-clip:text; color:transparent;
                text-shadow: 0 2px 8px rgba(0,0,0,.08);
                font-size: clamp(1.4rem, 2.2vw, 1.8rem);
              }
              #articles .am-text{ margin: 0; color:#374151; line-height: 1.7; }
              #articles .am-close{
                position: absolute; right: 10px; top: 10px;
                width: 36px; height: 36px; border-radius: 10px;
                border: 1px solid rgba(0,0,0,.06); background:#fff; cursor:pointer;
                box-shadow: 0 8px 16px rgba(0,0,0,.08);
                display: inline-flex; align-items: center; justify-content: center;
                font-size: 20px; color:#111827;
              }
              #articles .am-close:hover{ transform: scale(1.04); }
              @keyframes amPop {
                to { transform: translateY(0) scale(1); opacity: 1; }
              }
              @keyframes amFade {
                from { opacity: 0; } to { opacity: 1; }
              }
              @keyframes amHeroZoom {
                from { transform: scale(1.02); }
                to { transform: scale(1.08); }
              }
              @media (max-width: 992px){
                #articles .articles-grid{ grid-template-columns:1fr; }
              }
              @media (max-width: 640px){
                #articles .article-card{ grid-template-columns:1fr; }
                #articles .article-card img{ height:160px; grid-row:auto; }
              }
            </style>
            <div class="articles-grid">
              @php
                $rawLocale = app()->getLocale();
                $currentLocale = in_array($rawLocale, ['ru', 'uz', 'en']) ? $rawLocale : 'ru';
                $pickA = function ($article, string $base) use ($currentLocale) {
                  $field = $base . '_' . $currentLocale;
                  return $article->{$field}
                    ?? $article->{$base . '_ru'}
                    ?? $article->{$base . '_uz'}
                    ?? null;
                };
              @endphp
              @forelse(($articles ?? collect()) as $article)
                @php
                  $title = $pickA($article, 'title') ?? '';
                  $text = \App\Support\Content::excerpt($pickA($article, 'description') ?? '', 160);
                  $articleMedia = \App\Support\Media::responsive($article->photo, 'articles', [480, 960]);
                  $views = (int) ($article->views ?? 0);
                  $articleSlug = \Illuminate\Support\Str::slug($title ?: ('news-' . $article->id));
                @endphp
              <article class="article-card">
                  <img src="{{ $articleMedia['src'] }}" @if($articleMedia['srcset']) srcset="{{ $articleMedia['srcset'] }}" sizes="(min-width:993px) 240px, 100vw" @endif width="{{ $articleMedia['width'] }}" height="{{ $articleMedia['height'] }}" alt="{{ $title }}" loading="lazy" decoding="async" />
                  <h3>{{ $title }}</h3>
                  <p>{{ $text }}</p>
                  <div class="article-meta" style="display:flex;align-items:center;gap:10px;color:#6B7280;margin:6px 0 8px 0;">
                    <span style="display:inline-flex;align-items:center;gap:6px;">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/>
                        <path d="M8 5.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5z" fill="#6B7280"/>
                      </svg>
                      <span>{{ number_format($views, 0, '.', ' ') }}</span>
                    </span>
                  </div>
                  <a class="article-more" href="{{ route('articles.show', ['article' => $article, 'slug' => $articleSlug]) }}">{{ __('Читать далее') }} <span class="icon">→</span></a>
              </article>
              @empty
                <div class="text-secondary">{{ __('Пока нет статей') }}</div>
              @endforelse
            </div>
            <div style="display:flex;justify-content:center;margin-top:22px;"><a class="btn" href="{{ route('articles') }}" style="background:#347d3d;color:#fff;border-radius:14px;padding:13px 20px;text-decoration:none;">{{ __('content.article.back') }}</a></div>
          </div>
        </div>
      </section>

      <x-faq :items="__('site.home.faq')" />

      <section id="contacts" class="contacts">
        <div class="container">
          <div class="contacts-panel">
            <div class="contacts-grid">
              <div class="contact-info">
                <h2 class="contact-title">{{ __('Контакты') }}</h2>
                <div class="contact-blocks">
                  <div class="contact-block">
                    <div class="contact-label">{{ __('Адрес') }}</div>
                    <p class="contact-value">{{ __('Узбекистан, город Ташкент, Сергели район') }}</p>
                  </div>
                  <div class="contact-block">
                    <div class="contact-label">{{ __('Время работы') }}</div>
                    <p class="contact-value">{{ __('с 09:00 до 18:00, Пн-Сб') }}</p>
                  </div>
                  <div class="contact-row">
                    <span class="ci">☎</span>
                    <a href="tel:+998991018839" class="contact-link">+998 99 101 88 39</a>
                  </div>
                  <div class="contact-row">
                    <span class="ci">✉</span>
                    <a href="mailto:neo_labs2019@mail.ru" class="contact-link">neo_labs2019@mail.ru</a>
                  </div>
                  <div class="contact-cta">
                    <a class="btn contact-map" href="{{ route('contacts') }}#contacts-map">{{ __('Посмотреть на карте') }}</a>
                  </div>
                </div>
              </div>
              <div class="contact-form-card">
                <h3 class="contact-form-title">{{ __('Напишите нам') }}</h3>
                <p class="contact-form-desc">{{ __('Если у вас есть вопрос, предложение или хотите узнать больше — заполните форму') }}</p>
                <a class="btn contact-submit" href="{{ route('contacts') }}#contact-form-title" style="background:#347d3d;color:#fff;text-decoration:none;">{{ __('content.product.contact_cta') }}</a>
              </div>
            </div>
            <style>
              /* Panel */
              #contacts .contacts-panel{
                background: linear-gradient(135deg,#f1ffe9 0%, #e9fbe7 100%);
                border-radius:28px; padding:32px;
              }
              /* Grid */
              #contacts .contacts-grid{
                display:grid; grid-template-columns:1.15fr 1fr; gap:32px; align-items:start;
              }
              /* Left side */
              #contacts .contact-info{ padding:8px; color:#18361d; }
              #contacts .contact-title{
                margin:0 0 22px 0; font-weight:900; color:#5FBB46;
                font-size: clamp(2.1rem, 2.6vw, 2.8rem);
              }
              #contacts .contact-blocks{ display:flex; flex-direction:column; gap:14px; }
              #contacts .contact-label{
                font-weight:700; font-size:1.05rem; color:#5FBB46;
              }
              #contacts .contact-value{ margin:.3rem 0 0 0; line-height:1.6; font-size:1.02rem; }
              #contacts .contact-row{ display:flex; align-items:center; gap:10px; }
              #contacts .ci{
                width:34px; height:34px; border-radius:50%; background:#fff; border:1px solid #c6efcf;
                display:inline-flex; align-items:center; justify-content:center; color:#5FBB46; font-weight:700;
              }
              #contacts .contact-link{ font-size:1.08rem; font-weight:700; color:#5FBB46; text-decoration:none; }
              #contacts .contact-map{ background:#89D033; border-radius:999px; padding:12px 20px; display:inline-flex; align-items:center; gap:10px; }
              /* Right side card */
              #contacts .contact-form-card{
                background:#ffffff; border-radius:24px; padding:24px; box-shadow:0 18px 40px rgba(34,197,94,0.16);
              }
              #contacts .contact-form-title{
                margin:0 0 6px 0; font-weight:900; color:#5FBB46;
                font-size: clamp(1.8rem, 2.2vw, 2.4rem);
              }
              #contacts .contact-form-desc{ margin:0 0 16px 0; color:#6b7280; }
              #contacts .contact-form{ display:grid; gap:12px; }
              #contacts .contact-form input,
              #contacts .contact-form textarea{
                height:48px; border-radius:12px; border:1px solid #d7f0dc; background:#f6fff9; padding:12px 14px; font:inherit;
              }
              #contacts .contact-form textarea{ min-height:140px; height:auto; resize:vertical; }
              #contacts .contact-submit{ height:46px; border-radius:999px; background:#89D033; color:#fff; font-weight:700; align-self:flex-start; padding:10px 18px; }
              #contacts ::placeholder { color:#94a3b8; opacity:1; }
              /* Alerts */
              #contacts .contact-alert{ border-radius:12px; padding:12px 14px; margin:0 0 12px 0; font-weight:600; }
              #contacts .contact-alert.ok{ background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
              #contacts .contact-alert.err{ background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
              /* Responsive */
              @media (max-width: 960px){
                #contacts .contacts-grid{ grid-template-columns:1fr; }
              }
            </style>
          </div>
        </div>
      </section>
    </main>
</x-layouts.index>
