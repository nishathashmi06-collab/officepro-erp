@props(['value' => 0, 'showLabel' => true])
@php($v = max(0, min(100, (int) $value)))
@php($variant = $v >= 100 ? 'success' : ($v >= 60 ? '' : ($v >= 30 ? 'warning' : 'danger')))
<div class="d-flex align-items-center gap-2" {{ $attributes }}>
    <div class="op-progress flex-fill {{ $variant }}"><div class="bar" style="width: {{ $v }}%"></div></div>
    @if ($showLabel)<span class="small text-muted fw-semibold" style="min-width: 34px" data-progress-label>{{ $v }}%</span>@endif
</div>
