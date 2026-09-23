@extends('layouts.app')
@section('title', $department->name)
@section('content')
<x-page-header :title="$department->name" :subtitle="$department->description" :breadcrumbs="['Departments' => route('departments.index'), $department->name => null]">
    <x-slot:actions>
        <a href="{{ route('departments.edit', $department) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
    </x-slot:actions>
</x-page-header>
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><x-stat-card label="Manager" :value="$department->manager?->first_name ?? '—'" icon="bi-person-workspace" :meta="$department->manager?->designation?->name" /></div>
    <div class="col-md-3 col-6"><x-stat-card label="Active employees" :value="$stats['active']" icon="bi-people" color="success" /></div>
    <div class="col-md-3 col-6"><x-stat-card label="Open tasks" :value="$stats['open_tasks']" icon="bi-kanban" color="purple" /></div>
    <div class="col-md-3 col-6"><x-stat-card label="Monthly salary cost" :value="money($stats['monthly_salary'])" icon="bi-cash" color="info" /></div>
</div>
<x-card title="Employees in {{ $department->name }}" :padding="false">
    @if ($employees->isEmpty())
        <x-empty-state icon="bi-people" title="No employees in this department" />
    @else
        <x-table>
            <thead><tr><th>Employee</th><th>Designation</th><th>Joined</th><th>Status</th></tr></thead>
            <tbody>
            @foreach ($employees as $e)
                <tr data-href="{{ route('employees.show', $e) }}">
                    <td data-label=""><x-person :employee="$e" :href="route('employees.show', $e)" :sub="$e->email" /></td>
                    <td data-label="Designation">{{ $e->designation?->name ?? '—' }} @if($department->manager_id == $e->id)<span class="op-badge op-soft-primary no-dot ms-1">Manager</span>@endif</td>
                    <td data-label="Joined">{{ fmt_date($e->joining_date) }}</td>
                    <td data-label="Status"><x-status-badge :status="$e->status" /></td>
                </tr>
            @endforeach
            </tbody>
        </x-table>
        {{ $employees->links() }}
    @endif
</x-card>
@endsection
