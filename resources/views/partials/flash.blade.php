@foreach (['success' => 'check-circle-fill', 'error' => 'x-octagon-fill', 'warning' => 'exclamation-triangle-fill', 'info' => 'info-circle-fill', 'status' => 'info-circle-fill'] as $key => $icon)
    @if (session($key))
        <x-alert :type="$key === 'error' ? 'danger' : ($key === 'status' ? 'info' : $key)" :icon="$icon" autohide>{{ session($key) }}</x-alert>
    @endif
@endforeach
@if ($errors->any() && ! ($hideErrorSummary ?? false))
    <x-alert type="danger" icon="exclamation-octagon-fill">
        <strong>Please fix the highlighted {{ Str::plural('field', $errors->count()) }}.</strong>
        @if ($errors->count() <= 3)
            <ul class="mb-0 mt-1 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        @endif
    </x-alert>
@endif
