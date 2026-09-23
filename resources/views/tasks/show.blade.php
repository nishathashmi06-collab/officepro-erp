@extends('layouts.app')
@section('title', $task->title)
@section('content')
<x-page-header :title="$task->title" :breadcrumbs="['Tasks' => route('tasks.index'), Str::limit($task->title, 30) => null]">
    <x-slot:actions>
        @can('update', $task)
            <a href="{{ route('tasks.edit', $task) }}" class="btn btn-light"><i class="bi bi-pencil"></i> Edit</a>
            <x-delete-button :action="route('tasks.destroy', $task)" title="Delete this task?" label="Delete" size="" />
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-4">
    <div class="col-xl-8">
        <x-card class="mb-4">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <x-status-badge :status="$task->status" />
                <x-status-badge :status="$task->priority" :label="label($task->priority).' priority'" />
                @if ($task->isOverdue())<span class="op-badge op-soft-danger">Overdue</span>@endif
            </div>
            <div class="mb-4" style="white-space: pre-line">{{ $task->description ?: 'No description provided.' }}</div>
            <div class="small-caps mb-2">Progress</div>
            <x-progress :value="$task->progress" />

            @can('updateStatus', $task)
                <form method="POST" action="{{ route('tasks.status', $task) }}" class="row g-2 align-items-end mt-3 pt-3 border-top">
                    @csrf @method('PATCH')
                    <div class="col-sm-5">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select">
                            @foreach (App\Models\Task::STATUSES as $s)
                                @continue($s === 'cancelled' && ! auth()->user()->can('update', $task))
                                <option value="{{ $s }}" @selected($task->status === $s)>{{ label($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label small">Progress: <span id="p-out">{{ $task->progress }}%</span></label>
                        <input type="range" class="form-range" name="progress" min="0" max="100" step="5" value="{{ $task->progress }}" oninput="document.getElementById('p-out').textContent=this.value+'%'">
                    </div>
                    <div class="col-sm-3"><button class="btn btn-primary w-100"><i class="bi bi-arrow-repeat"></i> Update</button></div>
                </form>
            @endcan
        </x-card>

        <x-card title="Comments" icon="bi-chat-left-text" :subtitle="$task->comments->count().' comment(s)'">
            @can('comment', $task)
                <form method="POST" action="{{ route('tasks.comments.store', $task) }}" class="mb-4" novalidate>
                    @csrf
                    <div class="d-flex gap-2 align-items-start">
                        <x-avatar :name="auth()->user()->name" :src="auth()->user()->photo_url" size="36" />
                        <div class="flex-fill">
                            <textarea name="comment" rows="2" class="form-control @error('comment') is-invalid @enderror" placeholder="Write a comment…" required>{{ old('comment') }}</textarea>
                            @error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="text-end mt-2"><button class="btn btn-primary btn-sm"><i class="bi bi-send"></i> Comment</button></div>
                        </div>
                    </div>
                </form>
            @endcan
            @forelse ($task->comments as $comment)
                <div class="d-flex gap-2 mb-3">
                    <x-avatar :name="$comment->user?->name ?? 'User'" :src="$comment->user?->photo_url" size="36" />
                    <div class="flex-fill p-3 rounded-3" style="background: var(--op-surface-2)">
                        <div class="d-flex justify-content-between small mb-1"><strong>{{ $comment->user?->name ?? 'Deleted user' }}</strong><span class="text-muted">{{ $comment->created_at->diffForHumans() }}</span></div>
                        <div style="white-space: pre-line">{{ $comment->comment }}</div>
                    </div>
                </div>
            @empty
                <p class="text-muted small mb-0">No comments yet.</p>
            @endforelse
        </x-card>
    </div>
    <div class="col-xl-4">
        <x-card title="Details" class="mb-4">
            <dl class="op-dl">
                <dt>Assignee</dt><dd>@if($task->assignee)<a href="{{ route('employees.show', $task->assignee) }}">{{ $task->assignee->full_name }}</a>@else — @endif</dd>
                <dt>Created by</dt><dd>{{ $task->creator?->name ?? '—' }}</dd>
                <dt>Department</dt><dd>{{ $task->department?->name ?? '—' }}</dd>
                <dt>Due date</dt><dd class="{{ $task->isOverdue() ? 'text-danger' : '' }}">{{ fmt_date($task->due_date) }}</dd>
                <dt>Created</dt><dd>{{ $task->created_at->format('M d, Y') }}</dd>
                <dt>Completed</dt><dd>{{ $task->completed_at ? $task->completed_at->format('M d, Y') : '—' }}</dd>
            </dl>
        </x-card>
        <x-card title="Activity history" icon="bi-clock-history">
            @if ($history->isEmpty())
                <p class="text-muted small mb-0">No activity recorded.</p>
            @else
                <div class="op-timeline">
                    @foreach ($history as $log)
                        <div class="op-timeline-item">
                            <span class="dot"><i class="bi bi-dot"></i></span>
                            <div class="text">{{ $log->description }}</div>
                            <time>{{ $log->created_at->format('M d, h:i A') }}</time>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</div>
@endsection
