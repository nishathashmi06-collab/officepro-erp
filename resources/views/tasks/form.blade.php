@extends('layouts.app')
@php($editing = $task->exists)
@section('title', $editing ? 'Edit task' : 'New task')
@section('content')
<x-page-header :title="$editing ? 'Edit task' : 'New task'" :breadcrumbs="['Tasks' => route('tasks.index')] + ($editing ? [Str::limit($task->title, 30) => route('tasks.show', $task), 'Edit' => null] : ['New' => null])" />
<div class="row"><div class="col-xl-8">
<x-card>
    <form method="POST" action="{{ $editing ? route('tasks.update', $task) : route('tasks.store') }}" novalidate>
        @csrf @if ($editing) @method('PUT') @endif
        <x-form.input name="title" label="Title" :value="$task->title" required maxlength="200" placeholder="What needs to be done?" />
        <x-form.textarea name="description" label="Description" :value="$task->description" rows="4" placeholder="Details, acceptance criteria, links…" />
        <div class="row">
            <x-form.select class="col-md-6" name="assigned_to" label="Assign to" :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->full_name])" :value="$task->assigned_to" placeholder="Select employee…" required />
            <x-form.select class="col-md-6" name="department_id" label="Department" :options="$departments->pluck('name', 'id')" :value="$task->department_id" placeholder="Same as assignee" />
            <x-form.select class="col-md-4" name="priority" label="Priority" :options="collect(App\Models\Task::PRIORITIES)->mapWithKeys(fn ($p) => [$p => label($p)])" :value="$task->priority" required />
            <x-form.select class="col-md-4" name="status" label="Status" :options="collect(App\Models\Task::STATUSES)->mapWithKeys(fn ($p) => [$p => label($p)])" :value="$task->status" required />
            <x-form.input class="col-md-4" name="due_date" type="date" label="Due date" :value="$task->due_date?->toDateString()" :min="$editing ? null : today()->toDateString()" />
        </div>
        <div class="mb-3">
            <label for="f_progress" class="form-label d-flex justify-content-between">Progress <span class="text-muted" id="progress-out">{{ old('progress', $task->progress) }}%</span></label>
            <input type="range" class="form-range" min="0" max="100" step="5" name="progress" id="f_progress" value="{{ old('progress', $task->progress) }}" oninput="document.getElementById('progress-out').textContent = this.value + '%'">
        </div>
        <x-form.actions :cancel="$editing ? route('tasks.show', $task) : route('tasks.index')" :submit="$editing ? 'Save changes' : 'Create task'" />
    </form>
</x-card>
</div></div>
@endsection
