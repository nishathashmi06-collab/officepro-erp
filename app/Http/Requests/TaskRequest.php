<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task ? $this->user()->can('update', $task) : $this->user()->can('create', Task::class);
    }

    public function rules(): array
    {
        $user = $this->user();
        $assignable = Rule::exists('employees', 'id')->whereNull('deleted_at');

        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => [
                'required', $assignable,
                function ($attribute, $value, $fail) use ($user) {
                    // Managers may only assign within their own team.
                    if (! $user->hasPermission('tasks.manage_all') && ! in_array((int) $value, $user->teamEmployeeIds(), true)) {
                        $fail('You can only assign tasks to members of your team.');
                    }
                },
            ],
            'department_id' => ['nullable', 'exists:departments,id'],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'status' => ['required', Rule::in(Task::STATUSES)],
            'due_date' => array_filter(['nullable', 'date', $this->route('task') ? null : 'after_or_equal:today']),
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return ['assigned_to' => 'assignee', 'department_id' => 'department'];
    }
}
