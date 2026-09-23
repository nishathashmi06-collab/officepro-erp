<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public static function log(string $action, string $module, string $description, Model|int|null $record = null, ?int $userId = null): ActivityLog
    {
        $request = app()->runningInConsole() ? null : request();

        return ActivityLog::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'module' => $module,
            'record_id' => $record instanceof Model ? $record->getKey() : $record,
            'description' => mb_strimwidth($description, 0, 250, '…'),
            'ip_address' => $request?->ip(),
            'created_at' => now(),
        ]);
    }
}
