<?php

namespace App\Notifications;

use App\Models\Task;

class TaskDeadlineApproaching extends AppNotification
{
    public function __construct(public Task $task)
    {
    }

    public function type(): string
    {
        return 'task_deadline';
    }

    public function title(): string
    {
        return $this->task->isOverdue() ? 'Task overdue' : 'Task deadline approaching';
    }

    public function message(): string
    {
        return sprintf('"%s" is due %s.', $this->task->title, $this->task->due_date->format('M d, Y'));
    }

    public function url(): string
    {
        return route('tasks.show', $this->task);
    }

    public function icon(): string
    {
        return 'bi-alarm';
    }

    public function color(): string
    {
        return 'danger';
    }
}
