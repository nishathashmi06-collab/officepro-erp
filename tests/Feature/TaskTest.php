<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Notifications\TaskAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private function team(): array
    {
        $manager = $this->makeUser('manager');
        $dept = $this->makeDepartment('IT', $manager->employee);
        $manager->employee->update(['department_id' => $dept->id]);
        $member = $this->makeUser('employee', ['department_id' => $dept->id]);

        return [$manager, $member, $dept];
    }

    private function taskData(array $overrides = []): array
    {
        return $overrides + ['title' => 'Ship feature', 'description' => 'Details', 'priority' => 'high', 'status' => 'pending', 'progress' => 0, 'due_date' => now()->addDays(3)->toDateString()];
    }

    public function test_manager_assigns_task_to_team_member(): void
    {
        Notification::fake();
        [$manager, $member, $dept] = $this->team();

        $this->actingAs($manager)->post('/tasks', $this->taskData(['assigned_to' => $member->employee->id]))->assertRedirect();

        $task = Task::firstOrFail();
        $this->assertSame($dept->id, $task->department_id);
        $this->assertSame($manager->id, $task->created_by);
        Notification::assertSentTo($member, TaskAssigned::class);
        $this->assertDatabaseHas('activity_logs', ['module' => 'tasks', 'record_id' => $task->id]);
    }

    public function test_manager_cannot_assign_outside_team_and_employee_cannot_create(): void
    {
        [$manager, $member] = $this->team();
        $outsider = $this->makeEmployee();

        $this->actingAs($manager)->post('/tasks', $this->taskData(['assigned_to' => $outsider->id]))->assertSessionHasErrors('assigned_to');
        $this->actingAs($member)->post('/tasks', $this->taskData(['assigned_to' => $member->employee->id]))->assertForbidden();
    }

    public function test_validation(): void
    {
        [$manager, $member] = $this->team();
        $this->actingAs($manager)->post('/tasks', ['title' => '', 'priority' => 'extreme', 'status' => 'x', 'progress' => 150, 'assigned_to' => $member->employee->id])
            ->assertSessionHasErrors(['title', 'priority', 'status', 'progress']);
    }

    public function test_assignee_updates_status_via_kanban_json(): void
    {
        [$manager, $member] = $this->team();
        $task = Task::create($this->taskData(['assigned_to' => $member->employee->id, 'created_by' => $manager->id]));

        $this->actingAs($member)->patchJson(route('tasks.status', $task), ['status' => 'in_progress'])->assertOk()->assertJson(['status' => 'in_progress']);
        $this->actingAs($member)->patchJson(route('tasks.status', $task), ['status' => 'completed'])->assertOk()->assertJson(['progress' => 100]);
        $this->assertNotNull($task->fresh()->completed_at);

        // Only the owner can cancel
        $this->actingAs($member)->patchJson(route('tasks.status', $task), ['status' => 'cancelled'])->assertForbidden();
        // Assignee cannot edit or delete
        $this->actingAs($member)->get(route('tasks.edit', $task))->assertForbidden();
        $this->actingAs($member)->delete(route('tasks.destroy', $task))->assertForbidden();
    }

    public function test_unrelated_employee_cannot_view_or_move_task(): void
    {
        [$manager, $member] = $this->team();
        $stranger = $this->makeUser('employee');
        $task = Task::create($this->taskData(['assigned_to' => $member->employee->id, 'created_by' => $manager->id]));

        $this->actingAs($stranger)->get(route('tasks.show', $task))->assertForbidden();
        $this->actingAs($stranger)->patchJson(route('tasks.status', $task), ['status' => 'review'])->assertForbidden();
        $this->actingAs($stranger)->post(route('tasks.comments.store', $task), ['comment' => 'hi'])->assertForbidden();
    }

    public function test_comments_and_history(): void
    {
        [$manager, $member] = $this->team();
        $task = Task::create($this->taskData(['assigned_to' => $member->employee->id, 'created_by' => $manager->id]));

        $this->actingAs($member)->post(route('tasks.comments.store', $task), ['comment' => 'On it!'])->assertSessionHas('success');
        $this->actingAs($manager)->get(route('tasks.show', $task))->assertOk()->assertSee('On it!')->assertSee('Activity history');
    }

    public function test_list_and_board_render_and_update_delete(): void
    {
        [$manager, $member] = $this->team();
        $task = Task::create($this->taskData(['assigned_to' => $member->employee->id, 'created_by' => $manager->id]));

        $this->actingAs($manager)->get('/tasks?priority=high&overdue=1')->assertOk();
        $this->actingAs($manager)->get('/tasks/board')->assertOk()->assertSee('Ship feature');
        $this->actingAs($member)->get('/tasks?mine=1')->assertOk()->assertSee('Ship feature');

        $this->actingAs($manager)->put(route('tasks.update', $task), $this->taskData(['assigned_to' => $member->employee->id, 'title' => 'Renamed', 'progress' => 40]))->assertRedirect();
        $this->assertSame('Renamed', $task->fresh()->title);
        $this->actingAs($manager)->delete(route('tasks.destroy', $task))->assertRedirect('/tasks');
        $this->assertSoftDeleted($task);
    }

    public function test_deadline_reminder_command(): void
    {
        Notification::fake();
        [$manager, $member] = $this->team();
        Task::create($this->taskData(['assigned_to' => $member->employee->id, 'created_by' => $manager->id, 'due_date' => today()->toDateString()]));

        $this->artisan('officepro:task-deadlines')->assertSuccessful();
        Notification::assertSentTo($member, \App\Notifications\TaskDeadlineApproaching::class);
        $this->artisan('officepro:task-deadlines')->expectsOutputToContain('Sent 0');
    }
}
