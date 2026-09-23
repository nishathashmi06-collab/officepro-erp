<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'module', 'record_id', 'description', 'ip_address', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function icon(): string
    {
        return match ($this->module) {
            'employees' => 'bi-people',
            'attendance' => 'bi-fingerprint',
            'leaves' => 'bi-calendar2-check',
            'tasks' => 'bi-kanban',
            'payroll' => 'bi-cash-stack',
            'expenses' => 'bi-receipt',
            'assets' => 'bi-laptop',
            'documents' => 'bi-folder2-open',
            'departments' => 'bi-diagram-3',
            'users' => 'bi-person-gear',
            'auth' => 'bi-shield-lock',
            'settings' => 'bi-gear',
            default => 'bi-activity',
        };
    }
}
