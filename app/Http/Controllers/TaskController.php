<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $tasks = $this->filtered($request)
            ->with(['assignee', 'creator', 'department'])
            ->orderByRaw("CASE status WHEN 'completed' THEN 2 WHEN 'cancelled' THEN 3 ELSE 1 END")
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->paginate(15)
            ->withQueryString();

        return view('tasks.index', $this->filterData($user) + [
            'tasks' => $tasks,
            'counts' => Task::visibleTo($user)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function board(Request $request): View
    {
        $tasks = $this->filtered($request)
            ->with('assignee')
            ->where(fn ($q) => $q->where('status', '!=', 'cancelled')->orWhere('updated_at', '>=', now()->subDays(14)))
            ->orderByRaw('due_date IS NULL')->orderBy('due_date')
            ->limit(300)
            ->get();

        return view('tasks.board', $this->filterData($request->user()) + [
            'columns' => collect(Task::STATUSES)->mapWithKeys(fn ($s) => [$s => $tasks->where('status', $s)->values()]),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Task::class);

        return view('tasks.form', [
            'task' => new Task(['priority' => 'medium', 'status' => 'pending', 'progress' => 0, 'assigned_to' => $request->query('employee')]),
            'employees' => $this->assignableEmployees($request->user()),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $assignee = Employee::find($data['assigned_to']);
        $data['department_id'] ??= $assignee?->department_id;
        $data['created_by'] = $request->user()->id;
        $data['completed_at'] = $data['status'] === 'completed' ? now() : null;
        if ($data['status'] === 'completed') {
            $data['progress'] = 100;
        }

        $task = Task::create($data);
        activity('created', 'tasks', "{$request->user()->name} assigned \"{$task->title}\" to {$assignee->full_name}", $task);

        if ($assignee?->user && ! $assignee->user->is($request->user())) {
            $assignee->user->notify(new TaskAssigned($task));
        }

        return redirect()->route('tasks.show', $task)->with('success', 'Task created and assigned.');
    }

    public function show(Task $task): View
    {
        $this->authorize('view', $task);
        $task->load(['assignee.department', 'creator', 'department', 'comments.user']);

        return view('tasks.show', [
            'task' => $task,
            'history' => ActivityLog::with('user')->where('module', 'tasks')->where('record_id', $task->id)
                ->latest('created_at')->latest('id')->get(),
        ]);
    }

    public function edit(Request $request, Task $task): View
    {
        $this->authorize('update', $task);

        return view('tasks.form', [
            'task' => $task,
            'employees' => $this->assignableEmployees($request->user(), $task->assigned_to),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(TaskRequest $request, Task $task): RedirectResponse
    {
        $data = $request->validated();
        $previousAssignee = $task->assigned_to;
        $data = $this->applyStatusSideEffects($task, $data);

        $task->fill($data);
        $changes = collect($task->getDirty())->except(['updated_at', 'completed_at'])->keys()->map(fn ($k) => label($k))->implode(', ');
        $task->save();

        activity('updated', 'tasks', "{$request->user()->name} updated task".($changes ? " ({$changes})" : ''), $task);

        if ($previousAssignee != $task->assigned_to && ($user = $task->assignee?->user) && ! $user->is($request->user())) {
            $user->notify(new TaskAssigned($task));
        }

        return redirect()->route('tasks.show', $task)->with('success', 'Task updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);
        $task->delete();
        activity('deleted', 'tasks', "Deleted task \"{$task->title}\"", $task);

        return redirect()->route('tasks.index')->with('success', 'Task deleted.');
    }

    /** Used by the Kanban board (JSON) and the task page (form post). */
    public function updateStatus(Request $request, Task $task): JsonResponse|RedirectResponse
    {
        $this->authorize('updateStatus', $task);

        $data = $request->validate([
            'status' => ['required', Rule::in(Task::STATUSES)],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        // Only the task owner / managers may cancel.
        if ($data['status'] === 'cancelled' && ! $request->user()->can('update', $task)) {
            abort(403, 'Only the task owner can cancel a task.');
        }

        $old = $task->status;
        $oldProgress = $task->progress;
        $task->update($this->applyStatusSideEffects($task, $data));

        $parts = [];
        if ($old !== $task->status) {
            $parts[] = 'status '.label($old).' → '.label($task->status);
        }
        if ($oldProgress !== $task->progress) {
            $parts[] = "progress {$oldProgress}% → {$task->progress}%";
        }
        if ($parts) {
            activity('status', 'tasks', "{$request->user()->name} changed ".implode(', ', $parts), $task);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'status' => $task->status, 'progress' => $task->progress]);
        }

        return back()->with('success', 'Task updated.');
    }

    public function comment(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('comment', $task);
        $data = $request->validate(['comment' => ['required', 'string', 'max:2000']]);

        $task->comments()->create(['user_id' => $request->user()->id, 'comment' => $data['comment']]);
        activity('commented', 'tasks', "{$request->user()->name} commented on the task", $task);

        return back()->with('success', 'Comment added.');
    }

    private function applyStatusSideEffects(Task $task, array $data): array
    {
        $status = $data['status'] ?? $task->status;

        if ($status === 'completed') {
            $data['progress'] = 100;
            $data['completed_at'] = $task->completed_at ?? now();
        } else {
            $data['completed_at'] = null;
            if (($data['progress'] ?? $task->progress) >= 100 && $status !== 'cancelled') {
                $data['progress'] = 90;
            }
        }

        return $data;
    }

    private function filtered(Request $request)
    {
        $user = $request->user();

        return Task::visibleTo($user)
            ->when($request->query('q'), fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('priority'), fn ($q, $v) => $q->where('priority', $v))
            ->when($request->query('assignee'), fn ($q, $v) => $q->where('assigned_to', $v))
            ->when($request->query('department'), fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->query('mine'), fn ($q) => $q->where('assigned_to', $user->employee?->id ?? 0))
            ->when($request->query('overdue'), fn ($q) => $q->open()->whereDate('due_date', '<', today()));
    }

    private function filterData(User $user): array
    {
        return [
            'employees' => $this->assignableEmployees($user),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ];
    }

    private function assignableEmployees(User $user, ?int $include = null)
    {
        return Employee::query()
            ->where(fn ($q) => $q->where('status', 'active')->when($include, fn ($q) => $q->orWhere('id', $include)))
            ->when(! $user->hasPermission('tasks.manage_all'), fn ($q) => $q->whereIn('id', $user->teamEmployeeIds()))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'department_id']);
    }
}
