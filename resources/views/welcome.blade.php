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

      <section id="products" class="products products-recommended" aria-labelledby="recommended-products-title">
        <div class="container products-container">
          <div class="products-panel">
            <div class="section-head products-head">
              <span class="products-caption">{{ __('Рекомендуемые товары') }}</span>
              <h2 id="recommended-products-title" class="products-title">{{ __('Наши продукты') }}</h2>
            </div>
            <div class="nl-products-grid">
            @if(isset($products) && $products->count())
              @foreach($products as $product)
                <x-product-card :product="$product" />
              @endforeach
            @else
              <p class="products-empty">{{ __('Пока нет товаров.') }}</p>
            @endif
            </div>
          </div>
        </div>
      </section>

      <section id="articles" class="articles articles-home" aria-labelledby="home-articles-title">
        <div class="container articles-container">
          <div class="articles-panel">
            <div class="section-head articles-head">
              <span class="articles-caption">{{ __('Полезные советы и новости') }}</span>
              <h2 id="home-articles-title" class="articles-title">{{ __('Новые публикации о медицине') }}<br>{{ __('и здоровье') }}</h2>
            </div>
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
                  $text = \App\Support\Content::excerpt($pickA($article, 'description') ?? '', 180);
                  $text = $text !== '' ? $text : __('content.article.index_description');
                  $articleMedia = \App\Support\Media::responsive($article->photo, 'articles', [480, 960]);
                  $articleSlug = \Illuminate\Support\Str::slug($title ?: ('news-' . $article->id));
                @endphp
              <article class="article-card">
                <div class="article-card__media">
                  <img src="{{ $articleMedia['src'] }}" @if($articleMedia['srcset']) srcset="{{ $articleMedia['srcset'] }}" sizes="(min-width:1280px) 230px, (min-width:768px) 210px, 100vw" @endif width="{{ $articleMedia['width'] }}" height="{{ $articleMedia['height'] }}" alt="{{ $title }}" loading="lazy" decoding="async" />
                </div>
                <div class="article-card__body">
                  <h3>{{ $title }}</h3>
                  <p>{{ $text }}</p>
                  <a class="article-more" href="{{ route('articles.show', ['article' => $article, 'slug' => $articleSlug]) }}" aria-label="{{ __('Читать далее') }}: {{ $title }}">{{ __('Читать далее') }} <span class="icon" aria-hidden="true">→</span></a>
                </div>
              </article>
              @empty
                <p class="articles-empty">{{ __('Пока нет статей') }}</p>
              @endforelse
            </div>
          </div>
        </div>
      </section>

      <x-faq :items="__('site.home.faq')" />

      <section id="contacts" class="contacts">
        <div class="container home-contacts__container">
          <div class="contacts-panel">
            <div class="contacts-grid">
              <div class="contact-info">
                <h2 class="contact-title">{{ __('Контакты') }}</h2>
                <div class="contact-blocks">
                  <address class="contact-address">
                    <div class="contact-block">
                      <div class="contact-label">{{ __('Адрес') }}</div>
                      <p class="contact-value">{{ __('Узбекистан, город Ташкент, Сергели район') }}</p>
                    </div>
                  </address>
                  <div class="contact-block">
                    <div class="contact-label">{{ __('Время работы') }}</div>
                    <p class="contact-value">{{ __('с 09:00 до 18:00, Пн-Сб') }}</p>
                  </div>
                  <div class="contact-row">
                    <span class="ci" aria-hidden="true">☎</span>
                    <a href="tel:+998991018839" class="contact-link">+998 99 101 88 39</a>
                  </div>
                  <div class="contact-row">
                    <span class="ci" aria-hidden="true">✉</span>
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
                <p class="form-hint" id="home-required-hint">{{ __('content.contact.required_hint') }}</p>

                @if(session('contact_ok'))<div class="contact-alert ok" role="status">{{ session('contact_ok') }}</div>@endif
                @if(session('contact_error'))<div class="contact-alert err" role="alert">{{ session('contact_error') }}</div>@endif
                @if($errors->any())
                  <div class="contact-alert err" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <form class="contact-form" action="{{ route('contact.send') }}" method="post" aria-describedby="home-required-hint" novalidate data-home-contact-form>
                  @csrf
                  <input type="hidden" name="form_context" value="general">
                  <div class="honeypot" aria-hidden="true">
                    <label for="home-contact-website">Website</label>
                    <input id="home-contact-website" type="text" name="website" tabindex="-1" autocomplete="off">
                  </div>
                  <div class="home-contact-field">
                    <label for="home-contact-name">{{ __('content.contact.name') }} <span aria-hidden="true">*</span></label>
                    <input id="home-contact-name" type="text" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" placeholder="{{ __('content.contact.name') }}" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" @if($errors->has('name')) aria-describedby="home-contact-name-error" @endif>
                    @error('name')<span class="field-error" id="home-contact-name-error">{{ $message }}</span>@enderror
                  </div>
                  <div class="home-contact-field">
                    <label for="home-contact-phone">{{ __('content.contact.phone') }} <span aria-hidden="true">*</span></label>
                    <input id="home-contact-phone" type="tel" name="phone" value="{{ old('phone') }}" required maxlength="255" autocomplete="tel" inputmode="tel" placeholder="{{ __('content.contact.phone') }}" aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}" @if($errors->has('phone')) aria-describedby="home-contact-phone-error" @endif>
                    @error('phone')<span class="field-error" id="home-contact-phone-error">{{ $message }}</span>@enderror
                  </div>
                  <div class="home-contact-field">
                    <label for="home-contact-message">{{ __('content.contact.message') }} <span aria-hidden="true">*</span></label>
                    <textarea id="home-contact-message" name="message" required maxlength="2000" rows="5" placeholder="{{ __('content.contact.message') }}" aria-invalid="{{ $errors->has('message') ? 'true' : 'false' }}" @if($errors->has('message')) aria-describedby="home-contact-message-error" @endif>{{ old('message') }}</textarea>
                    @error('message')<span class="field-error" id="home-contact-message-error">{{ $message }}</span>@enderror
                  </div>
                  <button class="btn contact-submit" type="submit" data-label-default="{{ __('content.contact.send') }}" data-label-loading="{{ __('content.contact.sending') }}">
                    <span>{{ __('content.contact.send') }}</span>
                    <span aria-hidden="true">↗</span>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
    @push('scripts')
      <script>
        document.querySelectorAll('[data-home-contact-form]').forEach(function(form){form.addEventListener('submit',function(event){if(!form.checkValidity()){event.preventDefault();form.reportValidity();return}var button=form.querySelector('button[type="submit"]');if(button){button.disabled=true;button.setAttribute('aria-disabled','true');var label=button.querySelector('span');if(label){label.textContent=button.getAttribute('data-label-loading')}}})});
      </script>
    @endpush
</x-layouts.index>
