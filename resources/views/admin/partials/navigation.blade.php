@php
  // Cached (30s) so the badge doesn't add a DB round-trip to every page load.
  $newMessages = \Illuminate\Support\Facades\Cache::remember(
      'admin.nav.new_messages', 30,
      fn () => \App\Models\ContactMessage::query()->where('status', 'new')->count()
  );
  // Unread count for the company mailbox; cached and fail-safe so an IMAP outage never breaks the panel.
  $unreadMail = \Illuminate\Support\Facades\Cache::remember('admin.nav.unread_mail', 60, function () {
      try {
          $mailbox = app(\App\Services\MailboxService::class);
          return $mailbox->configured() ? $mailbox->unreadCount() : 0;
      } catch (\Throwable $e) {
          return 0;
      }
  });
  $groups = [
    'Boshqaruv' => [
      ['route' => 'dashboard', 'pattern' => 'dashboard', 'icon' => 'grid-1x2', 'label' => 'Umumiy ko‘rinish'],
      ['route' => 'dashboard.analytics', 'pattern' => 'dashboard.analytics*', 'icon' => 'bar-chart-line', 'label' => 'Sayt analitikasi'],
    ],
    'Kontent' => [
      ['route' => 'dashboard.products.index', 'pattern' => 'dashboard.products.*', 'icon' => 'box-seam', 'label' => 'Mahsulotlar'],
      ['route' => 'dashboard.articles.index', 'pattern' => 'dashboard.articles.*', 'icon' => 'newspaper', 'label' => 'Maqolalar'],
      ['route' => 'dashboard.certificates.index', 'pattern' => 'dashboard.certificates.*', 'icon' => 'patch-check', 'label' => 'Sertifikatlar'],
      ['route' => 'dashboard.company-facts.index', 'pattern' => 'dashboard.company-facts.*', 'icon' => 'building-check', 'label' => 'Kompaniya faktlari'],
      ['route' => 'dashboard.partners.index', 'pattern' => 'dashboard.partners.*', 'icon' => 'people', 'label' => 'Hamkorlar'],
    ],
    'Aloqa' => [
      ['route' => 'dashboard.messages.index', 'pattern' => 'dashboard.messages.*', 'icon' => 'chat-left-dots', 'label' => 'Murojaatlar', 'badge' => $newMessages],
      ['route' => 'dashboard.mail.index', 'pattern' => 'dashboard.mail.*', 'icon' => 'envelope', 'label' => 'Pochta', 'badge' => $unreadMail],
      ['route' => 'dashboard.telegram.index', 'pattern' => 'dashboard.telegram.*', 'icon' => 'telegram', 'label' => 'Telegram'],
    ],
  ];
@endphp
<nav class="admin-nav" aria-label="Asosiy admin bo‘limlari">
  @foreach($groups as $groupLabel => $items)
    <p class="admin-nav-label">{{ $groupLabel }}</p>
    <div class="admin-nav-group">
      @foreach($items as $item)
        @php $isActive = request()->routeIs($item['pattern']); @endphp
        <a class="admin-nav-link {{ $isActive ? 'active' : '' }}" href="{{ route($item['route']) }}" @if($isActive) aria-current="page" @endif>
          <span class="admin-nav-icon" aria-hidden="true"><i class="bi bi-{{ $item['icon'] }}"></i></span>
          <span class="admin-nav-text">{{ $item['label'] }}</span>
          @if(!empty($item['badge']) && $item['badge'] > 0)
            <span class="admin-nav-badge" aria-label="{{ $item['badge'] }} ta yangi">{{ $item['badge'] }}</span>
          @endif
        </a>
      @endforeach
    </div>
  @endforeach
</nav>
