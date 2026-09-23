<?php

namespace Tests\Feature;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_check_in_and_check_out_calculates_hours(): void
    {
        Carbon::setTestNow('2026-09-23 08:55:00');
        $user = $this->makeUser('employee');

        $this->actingAs($user)->post('/attendance/check-in')->assertSessionHas('success');
        $record = Attendance::firstOrFail();
        $this->assertSame('present', $record->status);
        $this->assertSame('08:55:00', $record->check_in);

        Carbon::setTestNow('2026-09-23 17:25:00');
        $this->actingAs($user)->post('/attendance/check-out')->assertSessionHas('success');
        $record->refresh();
        $this->assertSame('17:25:00', $record->check_out);
        $this->assertEquals(8.5, (float) $record->working_hours);
        $this->assertDatabaseHas('activity_logs', ['module' => 'attendance', 'action' => 'check_in']);
    }

    public function test_duplicate_check_in_is_prevented(): void
    {
        Carbon::setTestNow('2026-09-23 09:00:00');
        $user = $this->makeUser('employee');

        $this->actingAs($user)->post('/attendance/check-in');
        $this->actingAs($user)->post('/attendance/check-in')->assertSessionHas('error');
        $this->assertSame(1, Attendance::count());
    }

    public function test_late_and_half_day_statuses(): void
    {
        Carbon::setTestNow('2026-09-23 09:40:00');
        $user = $this->makeUser('employee');
        $this->actingAs($user)->post('/attendance/check-in');
        $this->assertSame('late', Attendance::first()->status);

        Carbon::setTestNow('2026-09-23 11:00:00');
        $this->actingAs($user)->post('/attendance/check-out');
        $this->assertSame('half_day', Attendance::first()->status);
    }

    public function test_cannot_check_out_without_check_in(): void
    {
        $user = $this->makeUser('employee');
        $this->actingAs($user)->post('/attendance/check-out')->assertSessionHas('error');
    }

    public function test_user_without_employee_profile_gets_friendly_error(): void
    {
        $user = $this->makeUser('admin', [], false);
        $this->actingAs($user)->post('/attendance/check-in')->assertSessionHas('error');
        $this->actingAs($user)->get('/attendance')->assertOk();
    }

    public function test_hr_can_create_and_edit_records_with_unique_rule(): void
    {
        $hr = $this->makeUser('hr_manager');
        $employee = $this->makeEmployee();

        $data = ['employee_id' => $employee->id, 'date' => '2026-09-01', 'status' => 'present', 'check_in' => '09:00', 'check_out' => '18:00'];
        $this->actingAs($hr)->post('/attendance', $data)->assertRedirect();
        $record = Attendance::firstOrFail();
        $this->assertEquals(9, (float) $record->working_hours);

        $this->actingAs($hr)->post('/attendance', $data)->assertSessionHasErrors('employee_id');

        $this->actingAs($hr)->put(route('attendance.update', $record), ['status' => 'absent'] + $data)->assertRedirect();
        $this->assertSame('absent', $record->fresh()->status);
        $this->assertNull($record->fresh()->check_in);

        $this->actingAs($hr)->post('/attendance', ['employee_id' => $employee->id, 'date' => now()->addDay()->toDateString(), 'status' => 'present', 'check_in' => '09:00'])
            ->assertSessionHasErrors('date');
    }

    public function test_employees_cannot_edit_attendance_or_see_others(): void
    {
        $user = $this->makeUser('employee');
        $other = $this->makeEmployee(['first_name' => 'Hidden', 'last_name' => 'Colleague']);
        $record = Attendance::create(['employee_id' => $other->id, 'date' => today()->toDateString(), 'status' => 'present', 'check_in' => '09:00:00']);

        $this->actingAs($user)->get('/attendance/create')->assertForbidden();
        $this->actingAs($user)->get(route('attendance.edit', $record))->assertForbidden();
        $this->actingAs($user)->delete(route('attendance.destroy', $record))->assertForbidden();
        $this->actingAs($user)->get('/attendance?period=today')->assertOk()->assertDontSee('Hidden Colleague');
    }

    public function test_filters_work(): void
    {
        $hr = $this->makeUser('hr_manager');
        foreach (['today', 'week', 'month'] as $period) {
            $this->actingAs($hr)->get('/attendance?period='.$period)->assertOk();
        }
        $this->actingAs($hr)->get('/attendance?period=custom&from=2026-09-01&to=2026-09-10&status=late')->assertOk();
        $this->actingAs($hr)->get('/attendance?period=custom&from=2026-09-10&to=2026-09-01')->assertSessionHasErrors('to');
    }

    public function test_mark_absent_command(): void
    {
        $present = $this->makeEmployee();
        $missing = $this->makeEmployee();
        Attendance::create(['employee_id' => $present->id, 'date' => '2026-09-23', 'status' => 'present', 'check_in' => '09:00:00']);

        $this->artisan('officepro:mark-absent', ['date' => '2026-09-23'])->assertSuccessful();
        $this->assertDatabaseHas('attendances', ['employee_id' => $missing->id, 'status' => 'absent']);
        $this->assertSame(2, Attendance::count());
    }
}
