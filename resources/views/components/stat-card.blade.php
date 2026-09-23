@props(['label', 'value', 'icon' => 'bi-bar-chart', 'color' => 'primary', 'meta' => null, 'href' => null])
@php($tag = $href ? 'a' : 'div')
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->class(['op-card op-stat', 'op-card-hover' => $href]) }}>
    <span class="icon op-soft-{{ $color }}"><i class="bi {{ $icon }}"></i></span>
    <div class="min-w-0">
        <div class="label">{{ $label }}</div>
        <div class="value">{{ $value }}</div>
        @if ($meta)<div class="meta">{{ $meta }}</div>@endif
    </div>
</{{ $tag }}>
