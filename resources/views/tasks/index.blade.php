@extends('layouts.app')
@section('title', 'Tasks')
@section('content')
<x-page-header title="Tasks" subtitle="Assign work, track progress and hit deadlines." :breadcrumbs="['Tasks' => null]">
    <x-slot:actions>
        @include('tasks._view-switch')
        @can('create', App\Models\Task::class)<a href="{{ route('tasks.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New task</a>@endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4">
    @foreach (['pending' => ['bi-hourglass', 'warning'], 'in_progress' => ['bi-play-circle', 'primary'], 'review' => ['bi-eye', 'purple'], 'completed' => ['bi-check2-circle', 'success'], 'cancelled' => ['bi-slash-circle', 'secondary']] as $s => [$icon, $color])
        <div class="col-6 col-md op-animate op-animate-{{ $loop->iteration }}"><x-stat-card :label="label($s)" :value="$counts[$s] ?? 0" :icon="$icon" :color="$color" :href="route('tasks.index', ['status' => $s])" /></div>
    @endforeach
</div>

<x-card :padding="false">
    <div class="op-card-body border-bottom">@include('tasks._filters', ['showStatus' => true])</div>
    @if ($tasks->isEmpty())
        <x-empty-state icon="bi-kanban" title="No tasks found" message="Create a task or adjust the filters.">
            @can('create', App\Models\Task::class)<a href="{{ route('tasks.create') }}" class="btn btn-primary">New task</a>@endcan
        </x-empty-state>
    @else
        <x-table>
            <thead><tr><th>Task</th><th>Assignee</th><th>Priority</th><th>Due</th><th style="min-width:150px">Progress</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @foreach ($tasks as $task)
                <tr data-href="{{ route('tasks.show', $task) }}">
                    <td data-label="Task"><a href="{{ route('tasks.show', $task) }}" class="fw-semibold text-reset">{{ $task->title }}</a><div class="small text-muted">{{ $task->department?->name ?? 'No department' }} · by {{ $task->creator?->name ?? '—' }}</div></td>
                    <td data-label="Assignee"><x-person :employee="$task->assignee" size="30" :sub="$task->assignee?->employee_code" /></td>
                    <td data-label="Priority"><x-status-badge :status="$task->priority" /></td>
                    <td data-label="Due" class="{{ $task->isOverdue() ? 'text-danger fw-semibold' : '' }}">{{ fmt_date($task->due_date) }}@if($task->isOverdue())<div class="small">Overdue</div>@endif</td>
                    <td data-label="Progress"><x-progress :value="$task->progress" /></td>
                    <td data-label="Status"><x-status-badge :status="$task->status" /></td>
                    <td class="actions" data-label="">
                        @can('update', $task)
                            <a href="{{ route('tasks.edit', $task) }}" class="btn btn-light btn-sm btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                            <x-delete-button :action="route('tasks.destroy', $task)" title="Delete this task?" />
                        @else
                            <a href="{{ route('tasks.show', $task) }}" class="btn btn-light btn-sm">Open</a>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </x-table>
        {{ $tasks->links() }}
    @endif
</x-card>
@endsection
