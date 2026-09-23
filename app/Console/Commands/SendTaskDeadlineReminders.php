<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskDeadlineApproaching;
use Illuminate\Console\Command;

class SendTaskDeadlineReminders extends Command
{
    protected $signature = 'officepro:task-deadlines {--days=1 : Remind about tasks due within this many days}';

    protected $description = 'Notify assignees about open tasks that are due soon or overdue';

    public function handle(): int
    {
        $count = 0;

        Task::open()
            ->whereNotNull('due_date')
            ->whereNull('deadline_notified_at')
            ->whereDate('due_date', '<=', now()->addDays((int) $this->option('days'))->toDateString())
            ->with('assignee.user')
            ->chunkById(100, function ($tasks) use (&$count) {
                foreach ($tasks as $task) {
                    if ($user = $task->assignee?->user) {
                        $user->notify(new TaskDeadlineApproaching($task));
                        $count++;
                    }
                    $task->forceFill(['deadline_notified_at' => now()])->saveQuietly();
                }
            });

        $this->info("Sent {$count} task deadline reminder(s).");

        return self::SUCCESS;
    }
}
