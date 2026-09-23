<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Leave extends Model
{
    use SoftDeletes;

    public const STATUSES = ['pending', 'approved', 'rejected', 'cancelled'];

    protected $fillable = [
        'employee_id', 'leave_type_id', 'start_date', 'end_date', 'days', 'reason',
        'attachment', 'status', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'reviewed_at' => 'datetime',
        'days' => 'decimal:1',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasPermission('leaves.view_all')) {
            return $query;
        }

        if ($user->hasPermission('leaves.view_team')) {
            return $query->whereIn('leaves.employee_id', $user->teamEmployeeIds());
        }

        return $query->where('leaves.employee_id', $user->employee?->id ?? 0);
    }

    public function scopeOverlapping(Builder $query, int $employeeId, string $start, string $end): Builder
    {
        return $query->where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start);
    }

    /** Working days (Mon–Fri) between two dates, inclusive. */
    public static function countDays(string $start, string $end): int
    {
        $days = 0;
        $date = Carbon::parse($start);
        $last = Carbon::parse($end);

        while ($date->lte($last)) {
            if (! $date->isWeekend()) {
                $days++;
            }
            $date->addDay();
        }

        return $days;
    }
}
