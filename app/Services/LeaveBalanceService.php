<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use Illuminate\Support\Collection;

class LeaveBalanceService
{
    /**
     * Balance per active leave type for the given year.
     *
     * @return Collection<int, object{type: LeaveType, allowed: int, used: float, pending: float, remaining: float}>
     */
    public function forEmployee(Employee $employee, ?int $year = null): Collection
    {
        $year ??= today()->year;

        $taken = Leave::where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'pending'])
            ->whereYear('start_date', $year)
            ->selectRaw('leave_type_id, status, SUM(days) as total')
            ->groupBy('leave_type_id', 'status')
            ->get();

        return LeaveType::where('status', 'active')->orderBy('name')->get()->map(function (LeaveType $type) use ($taken) {
            $used = (float) ($taken->first(fn ($r) => $r->leave_type_id == $type->id && $r->status === 'approved')?->total ?? 0);
            $pending = (float) ($taken->first(fn ($r) => $r->leave_type_id == $type->id && $r->status === 'pending')?->total ?? 0);

            return (object) [
                'type' => $type,
                'allowed' => (int) $type->days_per_year,
                'used' => $used,
                'pending' => $pending,
                // Unpaid / unlimited types (0 days) never run out.
                'remaining' => $type->days_per_year > 0 ? max(0, $type->days_per_year - $used - $pending) : null,
            ];
        });
    }

    public function remaining(Employee $employee, LeaveType $type, int $year): ?float
    {
        return $this->forEmployee($employee, $year)->firstWhere('type.id', $type->id)?->remaining;
    }
}
