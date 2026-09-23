@extends('layouts.app')
@section('title', 'Departments')
@section('content')
<x-page-header title="Departments" subtitle="Organise teams, assign managers and see headcount at a glance." :breadcrumbs="['Departments' => null]">
    <x-slot:actions>
        <form method="GET" class="d-flex gap-2">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search departments…">
        </form>
        <a href="{{ route('departments.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New department</a>
    </x-slot:actions>
</x-page-header>

@if ($departments->isEmpty())
    <x-card><x-empty-state icon="bi-diagram-3" title="No departments" message="Create your first department to organise employees."><a href="{{ route('departments.create') }}" class="btn btn-primary">New department</a></x-empty-state></x-card>
@else
    <div class="row g-3">
        @foreach ($departments as $department)
            <div class="col-md-6 col-xl-4 op-animate op-animate-{{ min($loop->iteration, 6) }}">
                <div class="op-card op-card-hover h-100 d-flex flex-column">
                    <div class="op-card-body flex-fill">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="op-list-icon op-soft-primary" style="width:46px;height:46px;font-size:1.2rem"><i class="bi bi-diagram-3"></i></span>
                            <x-status-badge :status="$department->status" />
                        </div>
                        <h3 class="h5 mb-1"><a href="{{ route('departments.show', $department) }}" class="text-reset">{{ $department->name }}</a> @if($department->code)<span class="text-muted small fw-normal">{{ $department->code }}</span>@endif</h3>
                        <p class="text-muted small mb-3">{{ Str::limit($department->description, 90) ?: 'No description.' }}</p>
                        <div class="d-flex align-items-center gap-2 small">
                            @if ($department->manager)
                                <x-avatar :name="$department->manager->full_name" :src="$department->manager->photo_url" size="28" />
                                <span><span class="text-muted">Manager:</span> <strong>{{ $department->manager->full_name }}</strong></span>
                            @else
                                <span class="text-muted"><i class="bi bi-person-dash"></i> No manager assigned</span>
                            @endif
                        </div>
                    </div>
                    <div class="op-card-footer d-flex justify-content-between align-items-center">
                        <span class="small"><strong>{{ $department->active_employees_count }}</strong> <span class="text-muted">active / {{ $department->employees_count }} total</span></span>
                        <div class="d-flex gap-1">
                            <a href="{{ route('departments.show', $department) }}" class="btn btn-light btn-sm btn-icon" title="View"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('departments.edit', $department) }}" class="btn btn-light btn-sm btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                            <x-delete-button :action="route('departments.destroy', $department)" title="Delete {{ $department->name }}?" message="Departments with employees cannot be deleted." />
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="op-card mt-3">{{ $departments->links() }}</div>
@endif
@endsection
