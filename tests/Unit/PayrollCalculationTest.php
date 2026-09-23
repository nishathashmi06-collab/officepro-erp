<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Payroll;
use PHPUnit\Framework\TestCase;

class PayrollCalculationTest extends TestCase
{
    public function test_gross_and_net_follow_the_documented_formula(): void
    {
        $totals = Payroll::calculate([
            'basic_salary' => 5000, 'allowances' => 500, 'overtime' => 250.50, 'bonus' => 300,
            'deductions' => 120, 'tax' => 440, 'other_deductions' => 30.25,
        ]);

        $this->assertSame(6050.50, $totals['gross']);
        $this->assertSame(5460.25, $totals['net']);
    }

    public function test_missing_components_count_as_zero(): void
    {
        $this->assertSame(['gross' => 1000.0, 'net' => 1000.0], Payroll::calculate(['basic_salary' => 1000]));
    }

    public function test_working_hours_are_calculated_including_overnight_shifts(): void
    {
        $this->assertSame(8.5, Attendance::calculateHours('09:00:00', '17:30:00'));
        $this->assertSame(8.0, Attendance::calculateHours('22:00', '06:00'));
        $this->assertSame(0.0, (float) Attendance::calculateHours('09:00:00', null));
    }

    public function test_leave_day_count_skips_weekends(): void
    {
        // Fri 2026-09-25 → Mon 2026-09-28 = 2 working days
        $this->assertSame(2, Leave::countDays('2026-09-25', '2026-09-28'));
        $this->assertSame(0, Leave::countDays('2026-09-26', '2026-09-27'));
    }
}
