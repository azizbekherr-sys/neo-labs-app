@props(['icon' => 'inbox', 'title', 'text' => null])
<div {{ $attributes->class(['admin-empty-state']) }}>
  <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
  <h3 class="h6">{{ $title }}</h3>
  @if($text)<p>{{ $text }}</p>@endif
  {{ $slot }}
</div>
