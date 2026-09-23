@props(['action', 'message' => 'This action cannot be undone.', 'title' => 'Delete this record?', 'label' => null, 'icon' => 'bi-trash', 'variant' => 'soft-danger', 'size' => 'sm', 'button' => 'Delete', 'method' => 'DELETE'])
<form method="POST" action="{{ $action }}" class="d-inline" data-confirm="{{ $message }}" data-confirm-title="{{ $title }}" data-confirm-button="{{ $button }}">
    @csrf
    @method($method)
    <button type="submit" {{ $attributes->class(['btn btn-'.$variant, 'btn-'.$size => $size, 'btn-icon' => ! $label]) }} @if(! $label) aria-label="{{ $button }}" data-bs-toggle="tooltip" title="{{ $button }}" @endif>
        <i class="bi {{ $icon }}"></i>{{ $label }}
    </button>
</form>
