@extends('layouts.app')
@section('title', 'Task board')
@section('content')
<x-page-header title="Task board" subtitle="Drag cards between columns to update their status." :breadcrumbs="['Tasks' => route('tasks.index'), 'Board' => null]">
    <x-slot:actions>
        @include('tasks._view-switch')
        @can('create', App\Models\Task::class)<a href="{{ route('tasks.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New task</a>@endcan
    </x-slot:actions>
</x-page-header>

<div class="op-card op-card-body mb-3">@include('tasks._filters')</div>

@php($colors = ['pending' => 'warning', 'in_progress' => 'primary', 'review' => 'purple', 'completed' => 'success', 'cancelled' => 'secondary'])
<div class="op-kanban" data-kanban>
    @foreach ($columns as $status => $tasks)
        <section class="op-kanban-col">
            <header><span><span class="op-dot-status op-soft-{{ $colors[$status] }}" style="background: currentColor"></span> {{ label($status) }}</span><span class="count">{{ $tasks->count() }}</span></header>
            <div class="op-kanban-list" data-status="{{ $status }}" data-label="{{ label($status) }}">
                <div class="text-center small text-muted py-4 {{ $tasks->isNotEmpty() ? 'd-none' : '' }}" data-empty>Drop tasks here</div>
                @foreach ($tasks as $task)
                    @php($canMove = auth()->user()->can('updateStatus', $task))
                    <article class="op-kanban-card" draggable="{{ $canMove ? 'true' : 'false' }}" data-id="{{ $task->id }}" data-url="{{ route('tasks.status', $task) }}">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <a href="{{ route('tasks.show', $task) }}" class="title">{{ $task->title }}</a>
                            <x-status-badge :status="$task->priority" />
                        </div>
                        <x-progress :value="$task->progress" class="my-2" />
                        <div class="d-flex justify-content-between align-items-center small">
                            <span class="d-flex align-items-center gap-1 text-muted text-truncate">
                                @if ($task->assignee)<x-avatar :name="$task->assignee->full_name" :src="$task->assignee->photo_url" size="22" /> {{ $task->assignee->first_name }}@endif
                            </span>
                            @if ($task->due_date)
                                <span class="{{ $task->isOverdue() ? 'text-danger fw-semibold' : 'text-muted' }}"><i class="bi bi-calendar-event"></i> {{ $task->due_date->format('M d') }}</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
@endsection
