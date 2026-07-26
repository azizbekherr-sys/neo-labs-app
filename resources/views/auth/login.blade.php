<!doctype html>
<html lang="uz">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Admin panelga kirish — NEO-LABS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
  <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
  <style>.admin-login-page{min-height:100dvh;display:grid;place-items:center;padding:1rem;background:radial-gradient(circle at 15% 15%,rgba(137,208,51,.14),transparent 28%),#f5f7fa}.admin-login-card{width:min(100%,440px)}.admin-login-logo{display:grid;place-items:center;width:54px;height:54px;margin:0 auto 1rem;border-radius:16px;background:var(--admin-brand);color:#173805;font-size:1.35rem;font-weight:900}.password-toggle{min-width:48px}</style>
</head>
<body>
  <main class="admin-login-page" id="main-content">
    <section class="admin-card admin-login-card" aria-labelledby="login-title"><div class="admin-card-body p-4 p-md-5">
      <div class="text-center mb-4"><div class="admin-login-logo" aria-hidden="true">N</div><h1 class="h3 mb-1" id="login-title">Admin panelga kirish</h1><p class="text-muted mb-0">NEO-LABS boshqaruv kabineti</p></div>
      @if($errors->any())<div class="alert alert-danger" role="alert" aria-live="assertive"><div class="fw-semibold">Kirish amalga oshmadi</div><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
      <form action="{{ route('login.post') }}" method="POST">@csrf
        <div class="mb-3"><label class="form-label" for="login-email">Email</label><input class="form-control @error('email') is-invalid @enderror" id="login-email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus @error('email') aria-invalid="true" aria-describedby="login-email-error" @enderror>@error('email')<div class="invalid-feedback" id="login-email-error">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label class="form-label" for="login-password">Parol</label><div class="input-group"><input class="form-control @error('password') is-invalid @enderror" id="login-password" type="password" name="password" autocomplete="current-password" required><button class="btn btn-outline-secondary password-toggle" type="button" id="password-toggle" aria-label="Parolni ko‘rsatish" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button></div></div>
        <div class="form-check mb-4"><input class="form-check-input" id="remember" type="checkbox" name="remember" value="1"><label class="form-check-label" for="remember">Ushbu qurilmada eslab qolish</label></div>
        <button class="btn btn-primary w-100" type="submit">Kirish</button>
      </form>
      @if(!\App\Models\User::query()->exists())<p class="text-center small mt-4 mb-0"><a href="{{ route('register') }}">Birinchi administrator hisobini yaratish</a></p>@endif
      <p class="text-center small mt-3 mb-0"><a href="{{ url('/uz') }}">Saytga qaytish</a></p>
    </div></section>
  </main>
  <script>document.getElementById('password-toggle').addEventListener('click',function(){var input=document.getElementById('login-password');var show=input.type==='password';input.type=show?'text':'password';this.setAttribute('aria-pressed',show?'true':'false');this.setAttribute('aria-label',show?'Parolni yashirish':'Parolni ko‘rsatish');this.querySelector('i').className=show?'bi bi-eye-slash':'bi bi-eye';});</script>
</body>
</html>
