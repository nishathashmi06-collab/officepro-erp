<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    public const STATUSES = ['present', 'late', 'half_day', 'absent', 'leave'];

    protected $fillable = [
        'employee_id', 'date', 'check_in', 'check_out', 'status',
        'working_hours', 'notes', 'check_in_ip', 'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'working_hours' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasPermission('attendance.view_all')) {
            return $query;
        }

        if ($user->hasPermission('attendance.view_team')) {
            return $query->whereIn('attendances.employee_id', $user->teamEmployeeIds());
        }

        return $query->where('attendances.employee_id', $user->employee?->id ?? 0);
    }

    /** Hours between check-in and check-out (handles overnight shifts). */
    public static function calculateHours(?string $checkIn, ?string $checkOut): float
    {
        if (! $checkIn || ! $checkOut) {
            return 0;
        }

        $in = Carbon::createFromFormat('H:i:s', strlen($checkIn) === 5 ? $checkIn.':00' : $checkIn);
        $out = Carbon::createFromFormat('H:i:s', strlen($checkOut) === 5 ? $checkOut.':00' : $checkOut);

        if ($out->lessThan($in)) {
            $out->addDay();
        }

        return round($in->diffInMinutes($out) / 60, 2);
    }
}
