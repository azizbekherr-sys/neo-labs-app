<x-layouts.index :title="__('site.contacts.title')" :description="__('site.contacts.description')" image="/img/neo-labs-contacts.webp" preload-image="/img/neo-labs-contacts.webp">
  @push('head')<link rel="stylesheet" href="{{ asset('css/content-pages.css') }}">@endpush
  <main class="content-page contact-page">
    <section class="content-hero contact-hero" aria-labelledby="contact-title">
      <div class="container content-hero__inner">
        <p class="content-eyebrow">NEO-LABS</p>
        <h1 id="contact-title">{{ __('site.contacts.title') }}</h1>
        <p>{{ __('site.contacts.description') }}</p>
      </div>
    </section>

    <section class="content-section" aria-labelledby="contact-form-title">
      <div class="container contact-layout">
        <div class="contact-details">
          <h2>{{ __('Свяжитесь с нами') }}</h2>
          <address>
            <div class="contact-item"><strong>{{ __('Позвоните нам') }}</strong><span>{{ __('content.contact.primary_phone') }}</span><a data-analytics="phone_click" href="tel:+998991018839">+998 99 101 88 39</a><span>{{ __('content.contact.additional_phone') }}</span><a data-analytics="phone_click" href="tel:+998974459639">+998 97 445 96 39</a></div>
            <div class="contact-item"><strong>Email</strong><a data-analytics="email_click" href="mailto:neo_labs2019@mail.ru">neo_labs2019@mail.ru</a></div>
            <div class="contact-item"><strong>{{ __('Наш адрес') }}</strong><span>{{ __('Узбекистан, город Ташкент, Сергели район') }}</span></div>
            <div class="contact-item"><strong>{{ __('Время работы') }}</strong><span>{{ __('с 09:00 до 18:00, Пн-Сб') }}</span></div>
          </address>
        </div>

        <div class="contact-form-card">
          <h2 id="contact-form-title">{{ __('Напишите нам') }}</h2>
          <p>{{ __('Если у вас есть вопрос, предложение или хотите узнать больше — заполните форму') }}</p>
          <p class="form-hint" id="required-hint">{{ __('content.contact.required_hint') }}</p>

          @if(session('contact_ok'))<div class="form-alert is-success" role="status">{{ session('contact_ok') }}</div>@endif
          @if(session('contact_error'))<div class="form-alert is-error" role="alert">{{ session('contact_error') }}</div>@endif
          @if($errors->any())
            <div class="form-alert is-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
          @endif

          <form class="accessible-form" action="{{ route('contact.send') }}" method="post" aria-describedby="required-hint" novalidate data-submit-form>
            @csrf
            <input type="hidden" name="form_context" value="general">
            <div class="honeypot" aria-hidden="true"><label for="contact-website">Website</label><input id="contact-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <div class="field-group">
              <label for="contact-name">{{ __('content.contact.name') }} <span aria-hidden="true">*</span></label>
              <input id="contact-name" type="text" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" @if($errors->has('name')) aria-describedby="contact-name-error" @endif>
              @error('name')<span class="field-error" id="contact-name-error">{{ $message }}</span>@enderror
            </div>
            <div class="field-group">
              <label for="contact-phone">{{ __('content.contact.phone') }} <span aria-hidden="true">*</span></label>
              <span class="field-hint" id="contact-phone-hint">{{ __('content.contact.phone_hint') }}</span>
              <input id="contact-phone" type="tel" name="phone" value="{{ old('phone') }}" required maxlength="255" autocomplete="tel" inputmode="tel" aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}" aria-describedby="contact-phone-hint{{ $errors->has('phone') ? ' contact-phone-error' : '' }}">
              @error('phone')<span class="field-error" id="contact-phone-error">{{ $message }}</span>@enderror
            </div>
            <div class="field-group">
              <label for="contact-message">{{ __('content.contact.message') }} <span aria-hidden="true">*</span></label>
              <textarea id="contact-message" name="message" required maxlength="2000" rows="6" aria-invalid="{{ $errors->has('message') ? 'true' : 'false' }}" @if($errors->has('message')) aria-describedby="contact-message-error" @endif>{{ old('message') }}</textarea>
              @error('message')<span class="field-error" id="contact-message-error">{{ $message }}</span>@enderror
            </div>
            <button class="primary-action submit-action" type="submit" data-label-default="{{ __('content.contact.send') }}" data-label-loading="{{ __('content.contact.sending') }}">{{ __('content.contact.send') }}</button>
          </form>
        </div>
      </div>
    </section>

    <section id="contacts-map" class="map-section" aria-labelledby="map-title">
      <div class="container">
        <h2 id="map-title">{{ __('content.contact.map_title') }}</h2>
        <iframe title="{{ __('content.contact.map_title') }}" src="https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d3000.2875468140473!2d69.21593707593007!3d41.237293971319204!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zNDHCsDE0JzE0LjMiTiA2OcKwMTMnMDYuNiJF!5e0!3m2!1sru!2s!4v1763994169490!5m2!1sru!2s" width="100%" height="380" style="border:0" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
    </section>
  </main>
  @push('scripts')
    <script>
      document.querySelectorAll('[data-submit-form]').forEach(function(form){form.addEventListener('submit',function(event){if(!form.checkValidity()){event.preventDefault();form.reportValidity();return}var button=form.querySelector('button[type="submit"]');if(button){button.disabled=true;button.setAttribute('aria-disabled','true');button.textContent=button.getAttribute('data-label-loading')}})});
    </script>
  @endpush
</x-layouts.index>
