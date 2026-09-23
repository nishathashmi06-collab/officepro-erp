@props(['variant' => 'primary', 'icon' => null, 'href' => null, 'type' => 'submit', 'size' => null])
@php($classes = 'btn btn-'.$variant.($size ? ' btn-'.$size : ''))
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>@if ($icon)<i class="bi {{ $icon }}"></i>@endif{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes]) }}>@if ($icon)<i class="bi {{ $icon }}"></i>@endif{{ $slot }}</button>
@endif
