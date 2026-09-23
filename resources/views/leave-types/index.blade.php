@extends('layouts.app')
@section('title', 'Leave types')
@section('content')
<x-page-header title="Leave types" subtitle="Configure the leave categories and yearly allowance." :breadcrumbs="['Leave' => route('leaves.index'), 'Types' => null]">
    <x-slot:actions><a href="{{ route('leave-types.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New leave type</a></x-slot:actions>
</x-page-header>
<x-card :padding="false">
    <x-table>
        <thead><tr><th>Name</th><th>Code</th><th>Days / year</th><th>Paid</th><th>Attachment</th><th>Requests</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @foreach ($types as $t)
            <tr>
                <td data-label="Name" class="fw-semibold"><span class="op-dot-status me-2 bg-{{ $t->color }}"></span>{{ $t->name }}</td>
                <td data-label="Code"><code>{{ $t->code }}</code></td>
                <td data-label="Days / year">{{ $t->days_per_year ?: 'Unlimited' }}</td>
                <td data-label="Paid">{!! $t->is_paid ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-dash-circle text-muted"></i>' !!}</td>
                <td data-label="Attachment">{{ $t->requires_attachment ? 'Required' : 'Optional' }}</td>
                <td data-label="Requests">{{ $t->leaves_count }}</td>
                <td data-label="Status"><x-status-badge :status="$t->status" /></td>
                <td class="actions" data-label="">
                    <a href="{{ route('leave-types.edit', $t) }}" class="btn btn-light btn-sm btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                    <x-delete-button :action="route('leave-types.destroy', $t)" title="Delete {{ $t->name }}?" />
                </td>
            </tr>
        @endforeach
        </tbody>
    </x-table>
</x-card>
@endsection
