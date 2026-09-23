<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base class for OfficePro notifications. Always stored in the database
 * (in-app bell); additionally e-mailed when the user opted in for the type.
 * Queued so mail delivery never slows down a request (QUEUE_CONNECTION=sync
 * runs it inline, "database" defers it to `php artisan queue:work`).
 */
abstract class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Preference key, e.g. "leave_request". */
    abstract public function type(): string;

    abstract public function title(): string;

    abstract public function message(): string;

    abstract public function url(): string;

    public function icon(): string
    {
        return 'bi-bell';
    }

    public function color(): string
    {
        return 'primary';
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (method_exists($notifiable, 'preference') && $notifiable->preference('email_notifications.'.$this->type(), false)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title().' – '.setting('company_name', 'OfficePro'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->message())
            ->action('Open in OfficePro', $this->url());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type(),
            'title' => $this->title(),
            'message' => $this->message(),
            'url' => $this->url(),
            'icon' => $this->icon(),
            'color' => $this->color(),
        ];
    }

    /** @return array<string, string> preference key => label */
    public static function types(): array
    {
        return [
            'leave_request' => 'New leave requests',
            'leave_reviewed' => 'Leave approved / rejected',
            'task_assigned' => 'Task assigned to me',
            'task_deadline' => 'Task deadline reminders',
            'payroll_generated' => 'Payroll generated',
            'document_expiring' => 'Documents expiring',
            'asset_assigned' => 'Asset assigned to me',
        ];
    }
}
