<?php

namespace App\Notifications;

use App\Models\Task;

class TaskAssigned extends AppNotification
{
    public function __construct(public Task $task)
    {
    }

    public function type(): string
    {
        return 'task_assigned';
    }

    public function title(): string
    {
        return 'New task assigned';
    }

    public function message(): string
    {
        return sprintf(
            '"%s" (%s priority)%s was assigned to you.',
            $this->task->title,
            ucfirst($this->task->priority),
            $this->task->due_date ? ', due '.$this->task->due_date->format('M d') : ''
        );
    }

    public function url(): string
    {
        return route('tasks.show', $this->task);
    }

    public function icon(): string
    {
        return 'bi-kanban';
    }
}
