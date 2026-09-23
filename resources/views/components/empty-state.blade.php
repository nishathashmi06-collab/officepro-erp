@props(['icon' => 'bi-inbox', 'title' => 'Nothing here yet', 'message' => null])
<div {{ $attributes->class(['op-empty']) }}>
    <div class="icon"><i class="bi {{ $icon }}"></i></div>
    <h3>{{ $title }}</h3>
    @if ($message)<p>{{ $message }}</p>@endif
    {{ $slot }}
</div>
