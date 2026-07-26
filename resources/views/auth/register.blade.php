<x-layouts.index>
  <main>
    <section class="contacts">
      <div class="container" style="max-width:520px;">
        <div class="section-head" style="margin-bottom:24px;">
          <span>Ro‘yxatdan o‘tish</span>
          <h2>Yangi hisob yaratish</h2>
        </div>

        @if ($errors->any())
          <div style="background:#fff3f3;border:1px solid #f5c2c7;color:#b02a37;padding:14px 16px;border-radius:12px;margin-bottom:16px;">
            <ul style="margin:0;padding-left:18px;">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('register.post') }}" method="POST">
          @csrf
          <input type="text" name="name" value="{{ old('name') }}" placeholder="To‘liq ism" required />
          <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required />
          <input type="password" name="password" placeholder="Parol (kamida 12 belgi)" minlength="12" required />
          <input type="password" name="password_confirmation" placeholder="Parolni tasdiqlang" required />
          <button type="submit" class="btn" style="width:100%;">Ro‘yxatdan o‘tish</button>
        </form>

        <div style="margin-top:12px; text-align:center;">
          <a href="{{ route('login') }}">Allaqachon hisobingiz bormi? Kirish</a>
        </div>
      </div>
    </section>
  </main>
</x-layouts.index>


