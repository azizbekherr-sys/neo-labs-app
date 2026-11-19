@extends('admin.layouts.app')
@section('title', 'Telegram bildirishnomalari')

@section('content')
  <header class="admin-page-header">
    <div>
      <span class="admin-eyebrow"><i class="bi bi-telegram" aria-hidden="true"></i>Aloqa</span>
      <h1 class="h3 mb-1">Telegram bildirishnomalari</h1>
      <p>Saytdagi formalardan kelgan murojaatlar va <strong>info@neo-labs.uz</strong> pochtasiga kelgan xatlar Telegram’ga yuboriladi.</p>
    </div>
    @if($bot && $bot['ok'])
      <span class="admin-filter-chip"><i class="bi bi-check-circle-fill" aria-hidden="true"></i>Bot ulangan: {{ '@' . $bot['username'] }}</span>
    @endif
  </header>

  <div class="row g-4">
    <div class="col-12 col-xl-7">
      <section class="admin-card" aria-labelledby="tg-settings-title">
        <div class="admin-card-header"><h2 class="h6 mb-0" id="tg-settings-title">Sozlamalar</h2></div>
        <div class="admin-card-body">
          @if($bot && !$bot['ok'])
            <div class="alert alert-warning small"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Saqlangan token ishlamayapti: {{ $bot['error'] }}</div>
          @endif
          <form method="POST" action="{{ route('dashboard.telegram.update') }}" autocomplete="off">
            @csrf
            <div class="mb-3">
              <label class="form-label" for="tg-token">Bot tokeni</label>
              <input class="form-control @error('bot_token') is-invalid @enderror" type="password" id="tg-token" name="bot_token" maxlength="120" autocomplete="new-password" spellcheck="false"
                     placeholder="{{ $hasToken ? 'Saqlangan ('.$tokenHint.'). O‘zgartirish uchun yangisini kiriting' : '123456789:AA...' }}">
              @error('bot_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="form-text">Token @BotFather’dan olinadi. U bazada shifrlangan holda saqlanadi va bu sahifada ko‘rsatilmaydi.@if($tokenFromEnv) Hozir .env faylidagi token ishlatilmoqda.@endif</div>
              @if($hasToken && !$tokenFromEnv)
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" id="tg-clear" name="clear_token" value="1">
                  <label class="form-check-label small" for="tg-clear">Saqlangan tokenni o‘chirish</label>
                </div>
              @endif
            </div>
            <div class="mb-3">
              <label class="form-label" for="tg-chat">Chat ID</label>
              <input class="form-control @error('chat_id') is-invalid @enderror" id="tg-chat" name="chat_id" value="{{ old('chat_id', $chatIds) }}" maxlength="300" placeholder="Masalan: 123456789 yoki -1001234567890" spellcheck="false">
              @error('chat_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="form-text">Bir nechta chat bo‘lsa vergul bilan ajrating. Bilmasangiz, o‘ngdagi “Chat ID ni topish” tugmasidan foydalaning.</div>
            </div>
            <fieldset class="mb-3">
              <legend class="form-label fs-6">Nimalar yuborilsin</legend>
              <input type="hidden" name="notify_forms" value="0">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="tg-forms" name="notify_forms" value="1" @checked(old('notify_forms', $notifyForms ? '1' : '0') === '1')>
                <label class="form-check-label" for="tg-forms">Saytdagi aloqa va kontrakt ishlab chiqarish formalari</label>
              </div>
              <input type="hidden" name="notify_mail" value="0">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="tg-mail" name="notify_mail" value="1" @checked(old('notify_mail', $notifyMail ? '1' : '0') === '1')>
                <label class="form-check-label" for="tg-mail">info@neo-labs.uz pochtasiga kelgan yangi xatlar (har daqiqada tekshiriladi)</label>
              </div>
            </fieldset>
            <button class="btn btn-primary" type="submit"><i class="bi bi-save me-2" aria-hidden="true"></i>Saqlash</button>
          </form>
        </div>
      </section>
    </div>

    <div class="col-12 col-xl-5">
      <section class="admin-card mb-4" aria-labelledby="tg-tools-title">
        <div class="admin-card-header"><h2 class="h6 mb-0" id="tg-tools-title">Tekshirish</h2></div>
        <div class="admin-card-body d-grid gap-2">
          <form method="POST" action="{{ route('dashboard.telegram.discover') }}">@csrf<button class="btn btn-outline-primary w-100" type="submit" @disabled(!$hasToken)><i class="bi bi-search me-2" aria-hidden="true"></i>Chat ID ni topish</button></form>
          <form method="POST" action="{{ route('dashboard.telegram.test') }}">@csrf<button class="btn btn-outline-success w-100" type="submit" @disabled(!$hasToken || $chatIds === '')><i class="bi bi-send me-2" aria-hidden="true"></i>Test xabar yuborish</button></form>
          @if($chats)
            <div class="mt-2">
              <p class="small text-muted mb-2">Botga yozgan chatlar. Kerakligini tanlang, keyin “Saqlash” ni bosing:</p>
              <ul class="list-unstyled d-grid gap-2 mb-0">
                @foreach($chats as $chat)
                  <li><button class="btn btn-sm btn-light w-100 text-start" type="button" data-tg-chat="{{ $chat['id'] }}"><strong>{{ $chat['title'] }}</strong> <span class="text-muted">· {{ $chat['type'] }} · {{ $chat['id'] }}</span></button></li>
                @endforeach
              </ul>
            </div>
          @endif
        </div>
      </section>

      <section class="admin-card" aria-labelledby="tg-help-title">
        <div class="admin-card-header"><h2 class="h6 mb-0" id="tg-help-title">Qanday ulash</h2></div>
        <div class="admin-card-body small">
          <ol class="mb-0 ps-3">
            <li class="mb-2">Telegram’da <strong>@BotFather</strong> → /mybots → botingiz → <em>API Token</em>. Tokenni nusxalab, chapdagi maydonga qo‘ying va “Saqlash” ni bosing.</li>
            <li class="mb-2">Xabarlar sizga kelsin desangiz, botni oching va <strong>/start</strong> bosing. Guruhga kelsin desangiz, botni guruhga qo‘shing va guruhda biror xabar yozing.</li>
            <li class="mb-2"><strong>Chat ID ni topish</strong> tugmasini bosing, ro‘yxatdan kerakli chatni tanlang va “Saqlash” ni bosing.</li>
            <li><strong>Test xabar yuborish</strong> bilan tekshiring.</li>
          </ol>
        </div>
      </section>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    document.querySelectorAll('[data-tg-chat]').forEach(function (button) {
      button.addEventListener('click', function () {
        var input = document.getElementById('tg-chat');
        var id = button.getAttribute('data-tg-chat');
        var list = input.value.split(/[\s,;]+/).filter(Boolean);
        if (list.indexOf(id) === -1) list.push(id);
        input.value = list.join(', ');
        input.focus();
      });
    });
  </script>
@endpush
