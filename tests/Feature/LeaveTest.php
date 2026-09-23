<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Notifications\LeaveRequested;
use App\Notifications\LeaveReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveTest extends TestCase
{
    use RefreshDatabase;

    private function nextMonday(int $weeks = 1): string
    {
        return now()->addWeeks($weeks)->startOfWeek()->toDateString();
    }

    private function setUpTeam(): array
    {
        $manager = $this->makeUser('manager');
        $dept = $this->makeDepartment('IT', $manager->employee);
        $manager->employee->update(['department_id' => $dept->id]);
        $employee = $this->makeUser('employee', ['department_id' => $dept->id]);
        $hr = $this->makeUser('hr_manager');

        return [$manager, $employee, $hr];
    }

    public function test_employee_requests_leave_and_approvers_are_notified(): void
    {
        Notification::fake();
        [$manager, $employee, $hr] = $this->setUpTeam();
        $type = LeaveType::where('code', 'AL')->first();
        $monday = $this->nextMonday();

        $this->actingAs($employee)->post('/leaves', [
            'leave_type_id' => $type->id, 'start_date' => $monday,
            'end_date' => now()->parse($monday)->addDays(6)->toDateString(), 'reason' => 'Family holiday',
        ])->assertRedirect();

        $leave = Leave::firstOrFail();
        $this->assertSame('pending', $leave->status);
        $this->assertEquals(5, (float) $leave->days); // Mon–Sun → 5 working days
        Notification::assertSentTo([$manager, $hr], LeaveRequested::class);
        Notification::assertNotSentTo($employee, LeaveRequested::class);
    }

    public function test_overlapping_requests_are_rejected(): void
    {
        [, $employee] = $this->setUpTeam();
        $type = LeaveType::where('code', 'CL')->first();
        $monday = $this->nextMonday();
        Leave::create(['employee_id' => $employee->employee->id, 'leave_type_id' => $type->id, 'start_date' => $monday, 'end_date' => $monday, 'days' => 1, 'reason' => 'x', 'status' => 'approved']);

        $this->actingAs($employee)->post('/leaves', ['leave_type_id' => $type->id, 'start_date' => $monday, 'end_date' => $monday, 'reason' => 'Another one'])
            ->assertSessionHasErrors('start_date');
    }

    public function test_balance_is_enforced(): void
    {
        [, $employee] = $this->setUpTeam();
        $type = LeaveType::where('code', 'EL')->first(); // 3 days
        $monday = $this->nextMonday();

        $this->actingAs($employee)->post('/leaves', ['leave_type_id' => $type->id, 'start_date' => $monday, 'end_date' => now()->parse($monday)->addDays(4)->toDateString(), 'reason' => 'Too long'])
            ->assertSessionHasErrors('leave_type_id');
    }

    public function test_attachment_required_types_and_private_storage(): void
    {
        Storage::fake('local');
        [, $employee] = $this->setUpTeam();
        $type = LeaveType::where('code', 'SL')->first();
        $type->update(['requires_attachment' => true]);
        $monday = $this->nextMonday();

        $this->actingAs($employee)->post('/leaves', ['leave_type_id' => $type->id, 'start_date' => $monday, 'end_date' => $monday, 'reason' => 'Flu symptoms'])
            ->assertSessionHasErrors('attachment');

        $this->actingAs($employee)->post('/leaves', ['leave_type_id' => $type->id, 'start_date' => $monday, 'end_date' => $monday, 'reason' => 'Flu symptoms', 'attachment' => UploadedFile::fake()->create('note.pdf', 50, 'application/pdf')])
            ->assertRedirect();
        $leave = Leave::firstOrFail();
        Storage::disk('local')->assertExists($leave->attachment);
        $this->actingAs($employee)->get(route('leaves.attachment', $leave))->assertOk();
    }

    public function test_manager_approves_team_leave_and_employee_is_notified(): void
    {
        Notification::fake();
        [$manager, $employee] = $this->setUpTeam();
        $leave = Leave::create(['employee_id' => $employee->employee->id, 'leave_type_id' => LeaveType::first()->id, 'start_date' => $this->nextMonday(), 'end_date' => $this->nextMonday(), 'days' => 1, 'reason' => 'Errand', 'status' => 'pending']);

        $this->actingAs($manager)->post(route('leaves.approve', $leave))->assertSessionHas('success');
        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertSame($manager->id, $leave->fresh()->reviewed_by);
        Notification::assertSentTo($employee, LeaveReviewed::class);
    }

    public function test_rejection_requires_reason_and_outsiders_cannot_review(): void
    {
        [$manager, $employee] = $this->setUpTeam();
        $otherManager = $this->makeUser('manager');
        $leave = Leave::create(['employee_id' => $employee->employee->id, 'leave_type_id' => LeaveType::first()->id, 'start_date' => $this->nextMonday(), 'end_date' => $this->nextMonday(), 'days' => 1, 'reason' => 'Errand', 'status' => 'pending']);

        $this->actingAs($otherManager)->post(route('leaves.approve', $leave))->assertForbidden();
        $this->actingAs($employee)->post(route('leaves.approve', $leave))->assertForbidden();
        $this->actingAs($manager)->post(route('leaves.reject', $leave))->assertSessionHasErrors('review_note');
        $this->actingAs($manager)->post(route('leaves.reject', $leave), ['review_note' => 'Release week'])->assertSessionHas('success');
        $this->assertSame('rejected', $leave->fresh()->status);
    }

    public function test_managers_cannot_approve_their_own_leave(): void
    {
        [$manager] = $this->setUpTeam();
        $leave = Leave::create(['employee_id' => $manager->employee->id, 'leave_type_id' => LeaveType::first()->id, 'start_date' => $this->nextMonday(), 'end_date' => $this->nextMonday(), 'days' => 1, 'reason' => 'Self', 'status' => 'pending']);
        $this->actingAs($manager)->post(route('leaves.approve', $leave))->assertForbidden();
    }

    public function test_employee_can_cancel_pending_leave(): void
    {
        [, $employee] = $this->setUpTeam();
        $leave = Leave::create(['employee_id' => $employee->employee->id, 'leave_type_id' => LeaveType::first()->id, 'start_date' => $this->nextMonday(), 'end_date' => $this->nextMonday(), 'days' => 1, 'reason' => 'x', 'status' => 'pending']);

        $this->actingAs($employee)->post(route('leaves.cancel', $leave))->assertSessionHas('success');
        $this->assertSame('cancelled', $leave->fresh()->status);
    }

    public function test_leave_pages_render(): void
    {
        [$manager, $employee] = $this->setUpTeam();
        $leave = Leave::create(['employee_id' => $employee->employee->id, 'leave_type_id' => LeaveType::first()->id, 'start_date' => $this->nextMonday(), 'end_date' => $this->nextMonday(), 'days' => 1, 'reason' => 'x', 'status' => 'pending']);

        $this->actingAs($employee)->get('/leaves')->assertOk()->assertSee('Annual Leave');
        $this->actingAs($employee)->get('/leaves/create')->assertOk();
        $this->actingAs($manager)->get(route('leaves.show', $leave))->assertOk()->assertSee('Review this request');
    }
}
