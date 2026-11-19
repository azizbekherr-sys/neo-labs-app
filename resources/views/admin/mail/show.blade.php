@extends('admin.layouts.app')
@section('title', $mail['subject'] . ' — Pochta')

@php
  $formatAddresses = function (array $list) {
      return collect($list)->map(fn ($a) => $a['name'] !== '' ? $a['name'] . ' <' . $a['email'] . '>' : $a['email'])->implode(', ');
  };
  $html = $mail['html'];
  if ($html !== '') {
      $html = preg_replace('#<(script|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html);
      $html = preg_replace('#<(script|iframe|object|embed|meta)\b[^>]*>#i', '', (string) $html);
      $srcdoc = '<!doctype html><html><head><meta charset="utf-8"><base target="_blank">'
          . '<style>html,body{margin:0;background:#fff;color:#1f2933}body{padding:16px;font:15px/1.55 -apple-system,Segoe UI,Arial,sans-serif;overflow-wrap:anywhere}img{max-width:100%;height:auto}table{max-width:100%}</style>'
          . '</head><body>' . $html . '</body></html>';
  }
  $humanSize = function (int $bytes) {
      return $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : max(1, (int) round($bytes / 1024)) . ' KB';
  };
@endphp

@section('content')
  <header class="admin-page-header">
    <div class="min-w-0">
      <span class="admin-eyebrow"><i class="bi bi-envelope-open" aria-hidden="true"></i>Pochta · {{ $folder === 'sent' ? 'Yuborilgan' : 'Kiruvchi' }}</span>
      <h1 class="h4 mb-1 admin-mail-title">{{ $mail['subject'] }}</h1>
      @if($mail['answered'])<p class="mb-0 text-success small"><i class="bi bi-reply-fill me-1" aria-hidden="true"></i>Javob berilgan</p>@endif
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('dashboard.mail.index', array_filter(['folder' => $folder === 'inbox' ? null : $folder])) }}"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Orqaga</a>
      @if($folder === 'inbox' && $replyTo)<a class="btn btn-primary" href="#reply"><i class="bi bi-reply me-2" aria-hidden="true"></i>Javob yozish</a>@endif
      <form id="delete-mail-{{ $mail['uid'] }}" method="POST" action="{{ route('dashboard.mail.destroy', ['folder' => $folder, 'uid' => $mail['uid']]) }}">@csrf @method('DELETE')</form>
      <button class="btn btn-outline-danger" type="button" data-confirm-delete data-form-id="delete-mail-{{ $mail['uid'] }}" data-object-label="{{ \Illuminate\Support\Str::limit($mail['subject'], 60) }} (xat savatga ko‘chiriladi)"><i class="bi bi-trash me-2" aria-hidden="true"></i>O‘chirish</button>
    </div>
  </header>

  <article class="admin-card mb-4" aria-labelledby="mail-meta-title">
    <div class="admin-card-header">
      <h2 class="visually-hidden" id="mail-meta-title">Xat ma’lumotlari</h2>
      <dl class="row small mb-0 admin-mail-meta">
        <dt class="col-3 col-md-2">Kimdan</dt><dd class="col-9 col-md-10">{{ $formatAddresses($mail['from']) ?: '—' }}</dd>
        <dt class="col-3 col-md-2">Kimga</dt><dd class="col-9 col-md-10">{{ $formatAddresses($mail['to']) ?: '—' }}</dd>
        @if($mail['cc'])<dt class="col-3 col-md-2">Nusxa</dt><dd class="col-9 col-md-10">{{ $formatAddresses($mail['cc']) }}</dd>@endif
        @if($mail['reply_to'] && $formatAddresses($mail['reply_to']) !== $formatAddresses($mail['from']))<dt class="col-3 col-md-2">Javob manzili</dt><dd class="col-9 col-md-10">{{ $formatAddresses($mail['reply_to']) }}</dd>@endif
        <dt class="col-3 col-md-2">Sana</dt><dd class="col-9 col-md-10 mb-0">{{ $mail['date'] ? $mail['date']->format('d.m.Y H:i') : '—' }}</dd>
      </dl>
    </div>
    <div class="admin-card-body p-0">
      @if($mail['html'] !== '')
        <iframe class="admin-mail-frame" title="Xat matni" sandbox="allow-popups allow-popups-to-escape-sandbox" referrerpolicy="no-referrer" srcdoc="{{ $srcdoc }}"></iframe>
      @elseif(trim($mail['text']) !== '')
        <div class="admin-mail-text">{{ $mail['text'] }}</div>
      @else
        <p class="text-muted p-3 mb-0">Xat matni bo‘sh.</p>
      @endif
    </div>
    @if($mail['attachments'])
      <div class="admin-card-body border-top">
        <h2 class="h6 mb-2"><i class="bi bi-paperclip me-1" aria-hidden="true"></i>Ilovalar ({{ count($mail['attachments']) }})</h2>
        <ul class="list-unstyled d-flex flex-wrap gap-2 mb-0">
          @foreach($mail['attachments'] as $file)
            <li><a class="btn btn-sm btn-outline-secondary" href="{{ route('dashboard.mail.attachment', ['folder' => $folder, 'uid' => $mail['uid'], 'part' => $file['part']]) }}"><i class="bi bi-download me-1" aria-hidden="true"></i>{{ $file['name'] }} <span class="text-muted">· {{ $humanSize($file['size']) }}</span></a></li>
          @endforeach
        </ul>
      </div>
    @endif
  </article>

  @if($folder === 'inbox')
    @if($replyTo)
      <section class="admin-card" id="reply" aria-labelledby="reply-title">
        <div class="admin-card-header"><h2 class="h6 mb-0" id="reply-title"><i class="bi bi-reply me-2" aria-hidden="true"></i>Javob yozish</h2></div>
        <div class="admin-card-body">
          <form method="POST" action="{{ route('dashboard.mail.reply', ['uid' => $mail['uid']]) }}" enctype="multipart/form-data">
            @csrf
            <p class="small text-muted mb-3">Kimga: <strong>{{ $replyTo['name'] !== '' ? $replyTo['name'] . ' <' . $replyTo['email'] . '>' : $replyTo['email'] }}</strong> · Kimdan: <strong>{{ $address }}</strong></p>
            <div class="mb-3">
              <label class="form-label" for="reply-body">Javob matni</label>
              <textarea class="form-control @error('body') is-invalid @enderror" id="reply-body" name="body" rows="8" maxlength="20000" required>{{ old('body') }}</textarea>
              @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
              <label class="form-label" for="reply-attachments">Fayl biriktirish <span class="text-muted small">(ixtiyoriy, 5 tagacha, har biri 10 MB gacha)</span></label>
              <input class="form-control admin-file-input @error('attachments.*') is-invalid @enderror" type="file" id="reply-attachments" name="attachments[]" multiple aria-describedby="reply-attachments-help">
              <div class="form-text" id="reply-attachments-help">Fayl tanlanmagan</div>
              @error('attachments.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-check mb-3">
              <input type="hidden" name="quote" value="0">
              <input class="form-check-input" type="checkbox" id="reply-quote" name="quote" value="1" @checked(old('quote', '1') === '1')>
              <label class="form-check-label" for="reply-quote">Asl xatni javob ostiga qo‘shish</label>
            </div>
            <button class="btn btn-primary" type="submit"><i class="bi bi-send me-2" aria-hidden="true"></i>Javobni yuborish</button>
          </form>
        </div>
      </section>
    @else
      <p class="text-muted">Bu xat uchun javob manzili topilmadi.</p>
    @endif
  @endif
@endsection
