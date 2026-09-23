@props(['status', 'label' => null])
@php
    $map = [
        // generic
        'active' => 'success', 'inactive' => 'secondary', 'disabled' => 'danger',
        // employees
        'on_leave' => 'info', 'resigned' => 'danger',
        // attendance
        'present' => 'success', 'late' => 'warning', 'half_day' => 'info', 'absent' => 'danger', 'leave' => 'purple',
        // leave / expenses
        'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary',
        // tasks
        'in_progress' => 'primary', 'review' => 'purple', 'completed' => 'success',
        'low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'urgent' => 'danger',
        // payroll
        'draft' => 'secondary', 'paid' => 'success',
        // assets
        'available' => 'success', 'assigned' => 'primary', 'maintenance' => 'warning', 'retired' => 'secondary',
        'new' => 'success', 'good' => 'success', 'fair' => 'info', 'poor' => 'warning', 'damaged' => 'danger',
        // documents
        'valid' => 'success', 'expiring' => 'warning', 'expired' => 'danger',
    ];
    $color = $map[$status] ?? 'secondary';
@endphp
<span {{ $attributes->class(['op-badge', 'op-soft-'.$color]) }}>{{ $label ?? label($status) }}</span>
