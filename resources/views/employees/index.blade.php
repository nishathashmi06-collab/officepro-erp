@extends('layouts.app')
@section('title', 'Employees')

@section('content')
<x-page-header title="Employees" subtitle="Manage your workforce, profiles and employment details." :breadcrumbs="['Employees' => null]">
    <x-slot:actions>
        @can('reports.view')
            <a href="{{ route('reports.export', ['employees', 'csv']) }}" class="btn btn-light"><i class="bi bi-download"></i> Export</a>
        @endcan
        @can('create', App\Models\Employee::class)
            <a href="{{ route('employees.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add employee</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<x-card :padding="false" class="op-animate op-animate-1">
    <form method="GET" class="op-card-body border-bottom op-filters">
        <div class="row g-2 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label small" for="q">Search</label>
                <div class="position-relative">
                    <i class="bi bi-search position-absolute text-muted" style="left:.8rem;top:50%;transform:translateY(-50%)"></i>
                    <input type="search" id="q" name="q" value="{{ request('q') }}" class="form-control ps-5" placeholder="Name, email, ID or phone">
                </div>
            </div>
            <div class="col-lg-2 col-md-6 col-6">
                <label class="form-label small">Department</label>
                <select name="department" class="form-select">
                    <option value="">All</option>
                    @foreach ($departments as $d)<option value="{{ $d->id }}" @selected(request('department') == $d->id)>{{ $d->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <label class="form-label small">Designation</label>
                <select name="designation" class="form-select">
                    <option value="">All</option>
                    @foreach ($designations as $d)<option value="{{ $d->id }}" @selected(request('designation') == $d->id)>{{ $d->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach (App\Models\Employee::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ label($s) }}</option>@endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-4 col-6 d-flex gap-2">
                <button class="btn btn-primary flex-fill"><i class="bi bi-funnel"></i> Filter</button>
                @if (request()->hasAny(['q', 'department', 'designation', 'status', 'type']))
                    <a href="{{ route('employees.index') }}" class="btn btn-light btn-icon" title="Reset"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </div>
    </form>

    @if ($employees->isEmpty())
        <x-empty-state icon="bi-people" title="No employees found" message="Try adjusting your filters or add a new employee.">
            @can('create', App\Models\Employee::class)
                <a href="{{ route('employees.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add employee</a>
            @endcan
        </x-empty-state>
    @else
        <x-table>
            <thead>
                <tr>
                    <th><x-sort-link column="first_name" label="Employee" :sort="$sort" :direction="$direction" /></th>
                    <th><x-sort-link column="employee_code" label="ID" :sort="$sort" :direction="$direction" /></th>
                    <th>Department</th>
                    <th>Type</th>
                    <th><x-sort-link column="joining_date" label="Joined" :sort="$sort" :direction="$direction" /></th>
                    @if ($canSeeSalary)<th class="text-end"><x-sort-link column="salary" label="Salary" :sort="$sort" :direction="$direction" /></th>@endif
                    <th><x-sort-link column="status" label="Status" :sort="$sort" :direction="$direction" /></th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($employees as $employee)
                    <tr data-href="{{ route('employees.show', $employee) }}">
                        <td data-label=""><x-person :employee="$employee" :href="route('employees.show', $employee)" :sub="$employee->email" /></td>
                        <td data-label="ID"><span class="font-monospace small">{{ $employee->employee_code }}</span></td>
                        <td data-label="Department">
                            <div class="fw-semibold">{{ $employee->department?->name ?? '—' }}</div>
                            <div class="small text-muted">{{ $employee->designation?->name }}</div>
                        </td>
                        <td data-label="Type">{{ label($employee->employment_type) }}</td>
                        <td data-label="Joined">{{ fmt_date($employee->joining_date) }}</td>
                        @if ($canSeeSalary)<td data-label="Salary" class="text-end fw-semibold">{{ money($employee->salary) }}</td>@endif
                        <td data-label="Status"><x-status-badge :status="$employee->status" /></td>
                        <td class="actions" data-label="">
                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-light btn-sm btn-icon" data-bs-toggle="tooltip" title="View profile"><i class="bi bi-eye"></i></a>
                            @can('update', $employee)
                                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-light btn-sm btn-icon" data-bs-toggle="tooltip" title="Edit"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @can('delete', $employee)
                                <x-delete-button :action="route('employees.destroy', $employee)" title="Delete {{ $employee->full_name }}?" message="The employee will be removed from lists and their login disabled. Attendance and payroll history is kept." />
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
        {{ $employees->links() }}
    @endif
</x-card>
@endsection
