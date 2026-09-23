@props(['id', 'title', 'size' => null, 'icon' => null])
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered {{ $size ? 'modal-'.$size : '' }}">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $id }}-label">@if($icon)<i class="bi {{ $icon }} text-primary me-2"></i>@endif{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{ $slot }}
        </div>
    </div>
</div>
