@props(['status'])
@php
  $map = [
    'active' => ['Faol', 'success', 'check-circle'],
    'draft' => ['Qoralama', 'secondary', 'pencil'],
    'paused' => ['To‘xtatilgan', 'warning', 'pause-circle'],
    'complete' => ['To‘liq', 'success', 'check-circle'],
    'incomplete' => ['To‘liq emas', 'warning', 'exclamation-circle'],
    'new' => ['Yangi', 'success', 'circle-fill'],
    'read' => ['Ko‘rildi', 'primary', 'eye'],
    'closed' => ['Yopildi', 'secondary', 'check2'],
    'sent' => ['Yuborildi', 'success', 'send-check'],
    'failed' => ['Yuborilmadi', 'warning', 'exclamation-triangle'],
    'pending' => ['Kutilmoqda', 'secondary', 'clock'],
  ];
  [$label, $tone, $icon] = $map[$status] ?? [ucfirst((string) $status), 'secondary', 'circle'];
@endphp
<span {{ $attributes->class(['badge', 'admin-status', 'admin-status-'.$tone]) }}><i class="bi bi-{{ $icon }}" aria-hidden="true"></i>{{ $label }}</span>
