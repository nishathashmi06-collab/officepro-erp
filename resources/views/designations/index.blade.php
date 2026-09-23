@extends('layouts.app')
@section('title', 'Designations')
@section('content')
<x-page-header title="Designations" subtitle="Job titles that can be assigned to employees." :breadcrumbs="['Designations' => null]">
    <x-slot:actions><a href="{{ route('designations.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New designation</a></x-slot:actions>
</x-page-header>
<x-card :padding="false">
    <form method="GET" class="op-card-body border-bottom op-filters"><input type="search" name="q" value="{{ request('q') }}" class="form-control" style="max-width:320px" placeholder="Search designations…"></form>
    @if ($designations->isEmpty())
        <x-empty-state icon="bi-person-badge" title="No designations found" />
    @else
        <x-table>
            <thead><tr><th>Designation</th><th>Description</th><th>Employees</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @foreach ($designations as $d)
                <tr>
                    <td data-label="Designation" class="fw-semibold">{{ $d->name }}</td>
                    <td data-label="Description" class="text-muted">{{ $d->description ?? '—' }}</td>
                    <td data-label="Employees"><a href="{{ route('employees.index', ['designation' => $d->id]) }}">{{ $d->employees_count }}</a></td>
                    <td data-label="Status"><x-status-badge :status="$d->status" /></td>
                    <td class="actions" data-label="">
                        <a href="{{ route('designations.edit', $d) }}" class="btn btn-light btn-sm btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                        <x-delete-button :action="route('designations.destroy', $d)" title="Delete {{ $d->name }}?" />
                    </td>
                </tr>
            @endforeach
            </tbody>
        </x-table>
        {{ $designations->links() }}
    @endif
</x-card>
@endsection
