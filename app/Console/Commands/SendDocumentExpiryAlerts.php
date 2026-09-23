<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentExpiring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendDocumentExpiryAlerts extends Command
{
    protected $signature = 'officepro:document-expiry';

    protected $description = 'Notify document managers (and the owning employee) about documents nearing expiry';

    public function handle(): int
    {
        $days = (int) setting('document_expiry_days', 30);
        $managers = User::active()->with('role.permissions')->get()
            ->filter(fn (User $u) => $u->hasPermission('documents.manage'));

        $count = 0;
        Document::expiringWithin($days)
            ->whereNull('expiry_notified_at')
            ->with('employee.user')
            ->chunkById(100, function ($documents) use ($managers, &$count) {
                foreach ($documents as $document) {
                    $recipients = $managers;
                    if ($document->employee_visible && $owner = $document->employee?->user) {
                        $recipients = $recipients->push($owner)->unique('id');
                    }
                    Notification::send($recipients, new DocumentExpiring($document));
                    $document->forceFill(['expiry_notified_at' => now()])->saveQuietly();
                    $count++;
                }
            });

        $this->info("Sent expiry alerts for {$count} document(s).");

        return self::SUCCESS;
    }
}
