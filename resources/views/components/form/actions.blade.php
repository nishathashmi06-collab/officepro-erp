@props(['cancel' => null, 'submit' => 'Save'])
<div class="d-flex flex-wrap justify-content-end gap-2 pt-3 mt-2 border-top">
    @if ($cancel)<a href="{{ $cancel }}" class="btn btn-light">Cancel</a>@endif
    {{ $slot }}
    <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i>{{ $submit }}</button>
</div>
