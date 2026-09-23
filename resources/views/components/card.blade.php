@props(['title' => null, 'subtitle' => null, 'icon' => null, 'padding' => true, 'footer' => null])
<div {{ $attributes->class(['op-card']) }}>
    @if ($title || isset($actions))
        <div class="op-card-header">
            <div class="min-w-0">
                @if ($title)<h2 class="h6">@if ($icon)<i class="bi {{ $icon }} text-primary me-2"></i>@endif{{ $title }}</h2>@endif
                @if ($subtitle)<div class="sub">{{ $subtitle }}</div>@endif
            </div>
            @isset($actions)<div class="d-flex align-items-center gap-2 flex-shrink-0">{{ $actions }}</div>@endisset
        </div>
    @endif
    @if ($padding)
        <div class="op-card-body">{{ $slot }}</div>
    @else
        {{ $slot }}
    @endif
    @if ($footer)
        <div class="op-card-footer">{{ $footer }}</div>
    @endif
</div>
