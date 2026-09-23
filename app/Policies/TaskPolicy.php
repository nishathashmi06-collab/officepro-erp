<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        if ($user->hasPermission('tasks.view_all') || $task->created_by == $user->id) {
            return true;
        }

        $employeeId = $user->employee?->id;
        if ($employeeId && $task->assigned_to == $employeeId) {
            return true;
        }

        return $user->hasPermission('tasks.create')
            && in_array($task->assigned_to, $user->teamEmployeeIds());
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.manage_all')
            || ($user->hasPermission('tasks.create') && $task->created_by == $user->id);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    /** The assignee may move their own task through the workflow. */
    public function updateStatus(User $user, Task $task): bool
    {
        return $this->update($user, $task)
            || ($user->employee && $task->assigned_to == $user->employee->id);
    }

    public function comment(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }
}
