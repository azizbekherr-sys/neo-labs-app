@extends('admin.layouts.app')
@section('title', 'Pochta')

@section('content')
  <header class="admin-page-header">
    <div>
      <span class="admin-eyebrow"><i class="bi bi-envelope" aria-hidden="true"></i>Aloqa</span>
      <h1 class="h3 mb-1">Pochta</h1>
      <p><strong>{{ $address }}</strong> pochtasiga kelgan xatlar. Xatni ochib, shu yerdan javob yozishingiz mumkin.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('dashboard.mail.compose') }}"><i class="bi bi-pencil-square me-2" aria-hidden="true"></i>Yangi xat</a>
  </header>

  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <ul class="nav nav-pills admin-mail-tabs" aria-label="Pochta papkalari">
      <li class="nav-item"><a class="nav-link {{ $folder === 'inbox' ? 'active' : '' }}" href="{{ route('dashboard.mail.index') }}" @if($folder === 'inbox') aria-current="page" @endif><i class="bi bi-inbox me-1" aria-hidden="true"></i>Kiruvchi</a></li>
      <li class="nav-item"><a class="nav-link {{ $folder === 'sent' ? 'active' : '' }}" href="{{ route('dashboard.mail.index', ['folder' => 'sent']) }}" @if($folder === 'sent') aria-current="page" @endif><i class="bi bi-send me-1" aria-hidden="true"></i>Yuborilgan</a></li>
    </ul>
    <form class="d-flex gap-2 admin-mail-search" method="GET" action="{{ route('dashboard.mail.index') }}" role="search" aria-label="Xatlarni qidirish">
      @if($folder !== 'inbox')<input type="hidden" name="folder" value="{{ $folder }}">@endif
      <label class="visually-hidden" for="mail-search">Qidiruv</label>
      <input class="form-control" id="mail-search" name="q" value="{{ $search }}" placeholder="Kimdan, mavzu yoki matn">
      <button class="btn btn-outline-primary" type="submit" aria-label="Qidirish"><i class="bi bi-search" aria-hidden="true"></i></button>
      @if($search !== '')<a class="btn btn-outline-secondary" href="{{ route('dashboard.mail.index', array_filter(['folder' => $folder === 'inbox' ? null : $folder])) }}">Tozalash</a>@endif
    </form>
  </div>

  @if($error)
    <div class="alert alert-warning" role="alert"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>{{ $error }}</div>
  @endif

  <section class="admin-card" aria-labelledby="mail-list-title">
    <div class="admin-card-header d-flex justify-content-between align-items-center">
      <h2 class="h6 mb-0" id="mail-list-title">{{ $folder === 'sent' ? 'Yuborilgan xatlar' : 'Kiruvchi xatlar' }}</h2>
      <span class="text-muted small">{{ $messages->total() }} ta xat</span>
    </div>
    @if($messages->count())
      <ul class="list-unstyled mb-0 admin-mail-list">
        @foreach($messages as $mail)
          @php
            $who = $folder === 'sent' ? $mail['to'] : $mail['from'];
            $isUnread = $folder === 'inbox' && !$mail['seen'];
          @endphp
          <li>
            <a class="admin-mail-row {{ $isUnread ? 'is-unread' : '' }}" href="{{ route('dashboard.mail.show', ['folder' => $folder, 'uid' => $mail['uid']]) }}">
              <span class="admin-mail-dot" aria-hidden="true"></span>
              <span class="admin-mail-who">{{ $folder === 'sent' ? 'Kimga: ' : '' }}{{ $who !== '' ? $who : '—' }}</span>
              <span class="admin-mail-subject">
                @if($mail['answered'])<i class="bi bi-reply-fill text-success me-1" title="Javob berilgan" aria-label="Javob berilgan"></i>@endif
                {{ $mail['subject'] }}
              </span>
              <span class="admin-mail-date">{{ $mail['date'] ? ($mail['date']->isToday() ? $mail['date']->format('H:i') : $mail['date']->format('d.m.Y')) : '' }}</span>
              @if($isUnread)<span class="visually-hidden">(o‘qilmagan)</span>@endif
            </a>
          </li>
        @endforeach
      </ul>
    @elseif(!$error)
      <x-admin.empty-state icon="envelope-open" :title="$search !== '' ? 'Hech narsa topilmadi' : ($folder === 'sent' ? 'Hali xat yuborilmagan' : 'Pochta bo‘sh')" :text="$folder === 'inbox' && $search === '' ? 'Bu yerda '.$address.' manziliga kelgan xatlar ko‘rinadi.' : null" />
    @endif
  </section>

  @if($messages->hasPages())<div class="mt-4">{{ $messages->links() }}</div>@endif
@endsection
