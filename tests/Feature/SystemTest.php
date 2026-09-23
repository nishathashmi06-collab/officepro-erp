<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Task;
use App\Notifications\TaskAssigned;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_real_counts(): void
    {
        $admin = $this->makeUser('admin');
        $e1 = $this->makeEmployee();
        $this->makeEmployee(['status' => 'inactive']);
        Attendance::create(['employee_id' => $e1->id, 'date' => today()->toDateString(), 'status' => 'late', 'check_in' => '09:45:00']);

        $response = $this->actingAs($admin)->get('/');
        $response->assertOk()->assertSee('Total Employees')->assertSee("what's happening in your office today.", false);
        $stats = $response->viewData('stats');
        $this->assertSame(3, $stats['total_employees']);
        $this->assertSame(2, $stats['active_employees']);
        $this->assertSame(1, $stats['present_today']);
        $this->assertSame(1, $stats['late_today']);
    }

    public function test_employee_dashboard_renders(): void
    {
        $this->actingAs($this->makeUser('employee'))->get('/')->assertOk()->assertSee('Leave Balance');
    }

    public function test_global_search_respects_permissions(): void
    {
        $admin = $this->makeUser('admin');
        $employee = $this->makeUser('employee');
        $this->makeEmployee(['first_name' => 'Searchable', 'last_name' => 'Person']);

        $this->actingAs($admin)->getJson('/search?q=Searchable')->assertOk()->assertJsonPath('groups.Employees.0.title', 'Searchable Person');
        $this->actingAs($employee)->getJson('/search?q=Searchable')->assertOk()->assertJsonMissingPath('groups.Employees');
        $this->actingAs($admin)->get('/search?q=Searchable')->assertOk()->assertSee('Searchable Person');
    }

    public function test_notifications_read_and_read_all(): void
    {
        $user = $this->makeUser('employee');
        $task = Task::create(['title' => 'Read me', 'assigned_to' => $user->employee->id, 'priority' => 'low', 'status' => 'pending']);
        $user->notifyNow(new TaskAssigned($task));
        $user->notifyNow(new TaskAssigned($task));
        $this->assertSame(2, $user->unreadNotifications()->count());

        $id = $user->notifications()->first()->id;
        $this->actingAs($user)->get(route('notifications.open', $id))->assertRedirect(route('tasks.show', $task));
        $this->assertSame(1, $user->fresh()->unreadNotifications()->count());

        $this->actingAs($user)->postJson('/notifications/read-all')->assertJson(['unread' => 0]);
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
        $this->actingAs($user)->get('/notifications')->assertOk()->assertSee('New task assigned');
    }

    public function test_activity_log_page_and_filters(): void
    {
        $super = $this->makeUser('super_admin');
        activity('created', 'employees', 'Created employee Foo Bar');
        $this->actingAs($super)->get('/activity-logs?module=employees&q=Foo')->assertOk()->assertSee('Created employee Foo Bar');
    }

    public function test_reports_export_csv_and_pdf(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeEmployee(['first_name' => 'Csv', 'last_name' => 'Row', 'notes' => '=cmd']);

        $this->actingAs($admin)->get('/reports')->assertOk()->assertSee('Payroll Report');
        foreach (['employees', 'attendance', 'leaves', 'payroll', 'expenses', 'tasks', 'assets', 'documents'] as $type) {
            $this->actingAs($admin)->get("/reports/{$type}?from=2020-01-01&to=2030-01-01")->assertOk();
        }

        $csv = $this->actingAs($admin)->get('/reports/employees/export/csv');
        $csv->assertOk();
        $content = $csv->streamedContent();
        $this->assertStringContainsString('"Employee ID",Name', $content);
        $this->assertStringContainsString('Csv Row', $content);

        $pdf = $this->actingAs($admin)->get('/reports/attendance/export/pdf');
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $this->actingAs($admin)->get('/reports/unknown')->assertNotFound();
        $this->actingAs($this->makeUser('hr_manager'))->get('/reports/expenses')->assertForbidden();
    }

    public function test_settings_update_and_apply(): void
    {
        $super = $this->makeUser('super_admin');
        $this->actingAs($super)->put('/settings', [
            'company_name' => 'Acme Ltd', 'company_email' => 'hi@acme.test', 'timezone' => 'Europe/London', 'date_format' => 'd/m/Y',
            'currency' => 'gbp', 'currency_symbol' => '£', 'work_start_time' => '08:30', 'work_end_time' => '17:00',
            'late_grace_minutes' => 10, 'default_working_hours' => 8, 'document_expiry_days' => 45,
        ])->assertSessionHas('success');

        $this->assertSame('Acme Ltd', Settings::get('company_name'));
        $this->assertSame('GBP', Settings::get('currency'));
        $this->assertSame('£1,234.50', money(1234.5));

        $this->actingAs($super)->put('/settings', ['company_name' => '', 'timezone' => 'Mars/Base', 'currency' => 'TOOLONG'])
            ->assertSessionHasErrors(['company_name', 'timezone', 'currency']);
    }

    public function test_profile_and_preferences(): void
    {
        $user = $this->makeUser('employee');
        $this->actingAs($user)->putJson('/profile/preferences', ['theme' => 'dark'])->assertOk();
        $this->assertSame('dark', $user->fresh()->preference('theme'));

        $this->actingAs($user)->put('/profile/preferences', ['section' => 'notifications', 'email_notifications' => ['task_assigned' => '1']])->assertSessionHas('success');
        $this->assertTrue($user->fresh()->preference('email_notifications.task_assigned'));
        $this->assertFalse($user->fresh()->preference('email_notifications.leave_reviewed'));

        $this->actingAs($user)->put('/profile/password', ['current_password' => 'wrong', 'password' => 'newpass-123', 'password_confirmation' => 'newpass-123'])->assertSessionHasErrors('current_password');
        $this->actingAs($user)->put('/profile/password', ['current_password' => 'password', 'password' => 'newpass-123', 'password_confirmation' => 'newpass-123'])->assertSessionHas('success');
    }

    public function test_error_pages_are_friendly(): void
    {
        $user = $this->makeUser('employee');
        $this->actingAs($user)->get('/does-not-exist')->assertNotFound()->assertSee('Page not found');
        $this->actingAs($user)->get('/users')->assertForbidden()->assertSee('Access denied');
    }
}
