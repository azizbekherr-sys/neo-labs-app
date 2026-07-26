<!doctype html>
<html lang="uz">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="theme-color" content="#176b35">
  <title>@yield('title', 'Admin panel') — NEO-LABS</title>
  <script>
    (function () {
      try {
        var t = localStorage.getItem('admin-theme');
        if (!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.documentElement.setAttribute('data-bs-theme', t);
      } catch (e) {}
    })();
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Raleway:wght@600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
  <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
  @stack('head')
</head>
<body data-open-modal="{{ $openModal ?? '' }}">
  <a class="admin-skip-link" href="#main-content">Asosiy kontentga o‘tish</a>

  <nav class="navbar admin-navbar sticky-top" aria-label="Yuqori navigatsiya">
    <div class="container-fluid px-3 px-lg-4">
      <button class="btn admin-menu-button d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminMobileNav" aria-controls="adminMobileNav" aria-label="Admin menyusini ochish">
        <i class="bi bi-list" aria-hidden="true"></i>
      </button>
      <a class="navbar-brand admin-brand" href="{{ route('dashboard') }}">
        <span class="admin-brand-mark" aria-hidden="true">N</span>
        <span class="admin-brand-text">NEO-LABS <small>Admin panel</small></span>
      </a>
      <div class="d-flex align-items-center gap-2 ms-auto">
        <button class="btn admin-theme-toggle" type="button" id="adminThemeToggle" aria-label="Mavzuni almashtirish" title="Yorug‘/qorong‘i rejim">
          <i class="bi bi-sun-fill admin-theme-icon-light" aria-hidden="true"></i>
          <i class="bi bi-moon-stars-fill admin-theme-icon-dark" aria-hidden="true"></i>
        </button>
        <a class="btn admin-view-site d-none d-sm-inline-flex" href="{{ url('/uz') }}" target="_blank" rel="noopener">
          <i class="bi bi-box-arrow-up-right me-2" aria-hidden="true"></i>Saytni ko‘rish
        </a>
        <div class="dropdown">
          <button class="btn admin-account-button" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="admin-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            <span class="d-none d-md-inline admin-account-name">{{ auth()->user()->name }}</span>
            <i class="bi bi-chevron-down d-none d-md-inline small" aria-hidden="true"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end admin-account-menu">
            <li><span class="dropdown-item-text"><span class="d-block fw-semibold">{{ auth()->user()->name }}</span><span class="small text-muted">{{ auth()->user()->email }}</span></span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item d-sm-none" href="{{ url('/uz') }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-2" aria-hidden="true"></i>Saytni ko‘rish</a></li>
            <li><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Chiqish</button></form></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <div class="offcanvas offcanvas-start admin-mobile-nav" tabindex="-1" id="adminMobileNav" aria-labelledby="adminMobileNavLabel">
    <div class="offcanvas-header">
      <h2 class="offcanvas-title h6 mb-0 d-flex align-items-center gap-2" id="adminMobileNavLabel"><span class="admin-brand-mark admin-brand-mark--sm" aria-hidden="true">N</span> Admin menyusi</h2>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Menyuni yopish"></button>
    </div>
    <div class="offcanvas-body">@include('admin.partials.navigation')</div>
  </div>

  <div class="admin-shell container-fluid px-3 px-lg-4 py-4">
    <div class="row g-4">
      <aside class="col-lg-3 col-xl-2 d-none d-lg-block" aria-label="Admin yon menyusi">
        <div class="admin-sidebar">@include('admin.partials.navigation')</div>
      </aside>
      <main class="col-12 col-lg-9 col-xl-10" id="main-content" tabindex="-1">
        @include('admin.partials.alerts')
        @yield('content')
      </main>
    </div>
  </div>

  <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmTitle" aria-describedby="deleteConfirmText" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title fs-5 d-flex align-items-center gap-2" id="deleteConfirmTitle"><span class="admin-modal-icon admin-modal-icon--danger" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></span> O‘chirishni tasdiqlang</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
        </div>
        <div class="modal-body"><p id="deleteConfirmText" class="mb-0">Tanlangan obyekt o‘chiriladi.</p></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Bekor qilish</button><button type="button" class="btn btn-danger" id="confirmDeleteButton"><i class="bi bi-trash me-2" aria-hidden="true"></i>O‘chirish</button></div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous" defer></script>
  <script src="{{ asset('js/admin.js') }}" defer></script>
  <script>
    (function () {
      var toggle = document.getElementById('adminThemeToggle');
      if (!toggle) return;
      toggle.addEventListener('click', function () {
        var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', next);
        try { localStorage.setItem('admin-theme', next); } catch (e) {}
      });
    })();
  </script>
  @stack('scripts')
</body>
</html>
