<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public const STATUSES = ['pending', 'in_progress', 'review', 'completed', 'cancelled'];

    protected $fillable = [
        'title', 'description', 'assigned_to', 'created_by', 'department_id',
        'priority', 'status', 'due_date', 'progress', 'completed_at', 'deadline_notified_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'deadline_notified_at' => 'datetime',
        'progress' => 'integer',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasPermission('tasks.view_all')) {
            return $query;
        }

        $employeeId = $user->employee?->id ?? 0;

        return $query->where(function (Builder $q) use ($user, $employeeId) {
            $q->where('tasks.assigned_to', $employeeId)->orWhere('tasks.created_by', $user->id);

            if ($user->hasPermission('tasks.create')) {
                $q->orWhereIn('tasks.assigned_to', $user->teamEmployeeIds());
            }
        });
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && ! $this->due_date->isToday()
            && ! in_array($this->status, ['completed', 'cancelled'], true);
    }
}
