<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class MarkAbsentEmployees extends Command
{
    protected $signature = 'officepro:mark-absent {date? : Date to process (defaults to today)}';

    protected $description = 'Create "absent" or "leave" attendance records for active employees who did not check in';

    public function handle(): int
    {
        $date = Carbon::parse($this->argument('date') ?? today());

        if ($date->isWeekend()) {
            $this->info('Weekend – nothing to do.');

            return self::SUCCESS;
        }

        $onLeave = Leave::where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->pluck('employee_id')
            ->all();

        $missing = Employee::whereIn('status', ['active', 'on_leave'])
            ->whereDate('joining_date', '<=', $date)
            ->whereDoesntHave('attendances', fn ($q) => $q->whereDate('date', $date))
            ->pluck('id');

        foreach ($missing as $employeeId) {
            Attendance::create([
                'employee_id' => $employeeId,
                'date' => $date->toDateString(),
                'status' => in_array($employeeId, $onLeave) ? 'leave' : 'absent',
                'notes' => 'Auto-generated',
            ]);
        }

        $this->info("Marked {$missing->count()} employee(s) for {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
