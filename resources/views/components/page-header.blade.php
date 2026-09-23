@props(['title', 'subtitle' => null, 'breadcrumbs' => []])
<div class="op-page-header op-animate">
    <div class="min-w-0">
        <x-breadcrumb :items="$breadcrumbs" />
        <h1>{{ $title }}</h1>
        @if ($subtitle)<p class="subtitle">{{ $subtitle }}</p>@endif
    </div>
    @if (isset($actions) && trim($actions) !== '')
        <div class="op-page-actions">{{ $actions }}</div>
    @endif
</div>
