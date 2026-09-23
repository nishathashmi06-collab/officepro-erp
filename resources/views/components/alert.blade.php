@props(['type' => 'info', 'icon' => null, 'dismissible' => true, 'autohide' => false])
@php($icon ??= ['success' => 'check-circle-fill', 'danger' => 'x-octagon-fill', 'warning' => 'exclamation-triangle-fill'][$type] ?? 'info-circle-fill')
<div {{ $attributes->class(['op-alert', 'op-alert-'.$type]) }} role="alert" @if($autohide) data-autohide @endif>
    <i class="bi bi-{{ $icon }} lead-icon"></i>
    <div class="flex-fill">{{ $slot }}</div>
    @if ($dismissible)
        <button type="button" class="btn-close" aria-label="Close" onclick="this.closest('.op-alert').remove()"></button>
    @endif
</div>
