@php
  $aboutTitle = __('site.about.title');
  $aboutDescription = __('site.about.description');
@endphp
<x-layouts.index :title="$aboutTitle" :description="$aboutDescription" image="/img/neo-labs-og.jpg" preload-image="/img/aboutbg.png">
  <main style="background:#ffffff;">
    <!-- Hero -->
    <section id="about-hero" style="padding:0 0 28px 0;">
      <div class="container" style="border-radius:28px; overflow:hidden; box-shadow:0 22px 60px rgba(17,94,50,.12);">
        <div style="position:relative; min-height:420px; background:url('{{ asset('img/aboutbg.png') }}') center/cover no-repeat;">
          <div style="position:absolute; inset:0; background:linear-gradient(0deg, rgba(0,0,0,.45), rgba(0,0,0,.1));"></div>
          <div style="position:relative; z-index:1; display:flex; align-items:center; justify-content:center; min-height:420px; padding:32px;">
            <h1 style="margin:0; color:#ffffff; font-weight:800; font-size:clamp(2.2rem, 4.2vw, 4rem); letter-spacing:.01em; text-align:center;">
              {{ __('О компании') }}
            </h1>
          </div>
        </div>
      </div>
    </section>

    <!-- Content -->
    <section id="about-content" style="padding:0 0 40px 0;">
      <div class="container" style="background:linear-gradient(135deg,#F3FFE9 0%, #EDFFD8 100%); border-radius:28px; padding:28px;">
        <div style="color:#1f2937; line-height:1.8; font-size:1rem;">
          <p style="margin:0 0 12px 0;">
            {{ __('Предприятие') }} <strong>{{ __('site.about.legal_name_display') }}</strong> {{ __('было создано 25 июня 2020 г. Предприятие является производителем биологически активных добавок.') }} {{ __('С начала функционирования на фармацевтическом рынке Республики «NEO‑LABS» зарекомендовало себя как предприятие по производству качественной востребованной продукции, которая рассчитана на широкие слои населения.') }}
          </p>
          <p style="margin:0 0 16px 0;">
            {{ __('Предприятие ставит своей целью соответствовать международным стандартам в области качества.') }}
          </p>
          <ul style="margin:0 0 12px 18px; padding:0; color:#334155;">
            <li>{{ __('Дата основания: 25 июня 2020 года.') }}</li>
            <li>{{ __('Профиль: выпуск BFQ полного цикла — от разработки до упаковки.') }}</li>
            <li>{{ __('Приоритет — стабильное качество, безопасность и доступность продукции.') }}</li>
            <li>{{ __('Система менеджмента построена на принципах GMP‑подхода и HACCP.') }}</li>
          </ul>
        </div>

        <div class="about-grid" style="display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:18px; margin-top:10px;">
          <img src="{{ asset('img/about1.png') }}" alt="about-1" style="width:100%; height:100%; object-fit:cover; border-radius:18px; box-shadow:0 14px 34px rgba(17,94,50,.08);" />
          <img src="{{ asset('img/about2.png') }}" alt="about-2" style="width:100%; height:100%; object-fit:cover; border-radius:18px; box-shadow:0 14px 34px rgba(17,94,50,.08);" />
        </div>
      </div>
      <style>
        @media (max-width: 992px){
          #about-content .about-grid { grid-template-columns: 1fr !important; }
        }
      </style>
    </section>
    <!-- More content -->
    <section id="about-more" style="padding:0 0 40px 0;">
      <div class="container" style="background:linear-gradient(135deg,#F3FFE9 0%, #EDFFD8 100%); border-radius:28px; padding:28px;">
        <div style="color:#1f2937; line-height:1.8; font-size:1rem;">
          <p style="margin:0 0 12px 0;">
            {{ __('Производство BFQ-продукции включает полный цикл — изготовление таблеток и капсул, саше-пакетов, сиропов и растворов на основе субстанций и вспомогательных веществ.') }}
          </p>
          <p style="margin:0 0 12px 0;">
            {{ __('В этом 2025 году компания инвестирует в линию " мягкую желатиновую капсулу " 455 000 долларов США и будет наложено новая линейка качественных БАД препаратов.') }}
          </p>
          <p style="margin:0 0 12px 0;">
            {{ __('Компания активно сотрудничает с ведущими Институтами Узбекистана как Ташкентский Фармацевтический Институт, Биорганик Кимё Институт и т.д.') }}
          </p>
          <p style="margin:0 0 12px 0;">
            {{ __('Хранение субстанций, вспомогательных веществ, сырья «in bulk», упаковочных материалов и печатной продукции, готовой продукции, разрешенной к реализации, осуществляется на складах. Соблюдаются требования по обеспечению сохранности продукции. Склады охраняются сотрудниками Службы Безопасности, условия хранения на складах контролируются с помощью термоэлектронных гигрометров и поддерживаются при помощи системы приточно–вытяжной вентиляции и кондиционирования.') }}
          </p>
          <p style="margin:0 0 16px 0;">
            {{ __('Хранение субстанций, вспомогательных веществ, сырья «in bulk», упаковочных материалов и печатной продукции, готовой продукции, разрешенной к реализации, осуществляется на складах. Соблюдаются требования по обеспечению сохранности продукции. Склады охраняются сотрудниками Службы Безопасности, условия хранения на складах контролируются с помощью термоэлектронных гигрометров и поддерживаются при помощи системы приточно–вытяжной вентиляции и кондиционирования.') }}
          </p>
        </div>
        <div class="about-grid" style="display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:18px; margin-top:10px;">
          <img src="{{ asset('img/about3.png') }}" alt="about-3" style="width:100%; height:100%; object-fit:cover; border-radius:18px; box-shadow:0 14px 34px rgba(17,94,50,.08);" />
          <img src="{{ asset('img/about4.png') }}" alt="about-4" style="width:100%; height:100%; object-fit:cover; border-radius:18px; box-shadow:0 14px 34px rgba(17,94,50,.08);" />
        </div>
      </div>
      <style>
        @media (max-width: 992px){
          #about-more .about-grid { grid-template-columns: 1fr !important; }
        }
      </style>
    </section>
    <!-- Kaizen section -->
    <section id="about-kaizen" style="padding:0 0 40px 0;">
      <div class="container" style="background:linear-gradient(135deg,#F3FFE9 0%, #EDFFD8 100%); border-radius:28px; padding:28px;">
        <div style="display:grid; grid-template-columns:1fr; gap:18px;">
          <div style="color:#1f2937; line-height:1.8; font-size:1rem;">
            <div style="font-weight:800; margin:0 0 10px 0;">
              {{ __('На производстве ООО «NEO‑LABS» функционирует японская система Кайдзен.') }}
            </div>
            <p style="margin:0 0 10px 0;">
              {{ __('Слово "kayzen" означает изменение к улучшению. Философия Кайдзен направлена на постоянное совершенствование. Более того, в непрерывном процессе улучшения вовлечены все сотрудники нашей компании, что позволяет непрерывно улучшать и оптимизировать процессы на всех уровнях. Так же практика Кайдзен показала, что не снижая качества выпускаемой продукции, можно добиться таких результатов, как рост производительности, сокращение срока выполнения процесса, снижение затрат, повышение уровня комфорта для сотрудников и развитие у них творческих навыков, снижение брака и повышение эффективности персонала.') }}
            </p>
          </div>
          <div style="display:flex; justify-content:center;">
            <img src="{{ asset('img/about5.png') }}" alt="Kaizen" style="max-width:360px; width:100%; height:auto; border-radius:18px;" />
          </div>
        </div>
      </div>
    </section>
  </main>
  <x-faq :items="__('site.about.faq')" />
</x-layouts.index>


