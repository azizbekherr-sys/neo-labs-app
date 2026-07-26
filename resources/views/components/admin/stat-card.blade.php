@props(['href', 'label', 'value', 'icon' => 'bar-chart', 'tone' => 'primary'])
<a href="{{ $href }}" {{ $attributes->class(['admin-stat-card', 'admin-stat-'.$tone]) }}>
  <span class="admin-stat-icon"><i class="bi bi-{{ $icon }}" aria-hidden="true"></i></span>
  <span><span class="admin-stat-label">{{ $label }}</span><strong class="admin-stat-value">{{ $value }}</strong></span>
  <i class="bi bi-arrow-right admin-stat-arrow" aria-hidden="true"></i>
</a>
