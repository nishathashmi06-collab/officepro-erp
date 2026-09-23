@props(['name' => '', 'src' => null, 'size' => 36])
@php
    $parts = preg_split('/\s+/', trim((string) $name));
    $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1).(count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
    $variant = 'c'.(abs(crc32((string) $name)) % 6);
@endphp
<span {{ $attributes->class(['op-avatar', $variant]) }} style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ max(10, round($size * .38)) }}px">
    @if ($src)
        <img src="{{ $src }}" alt="{{ $name }}" loading="lazy">
    @else
        {{ $initials ?: '?' }}
    @endif
</span>
