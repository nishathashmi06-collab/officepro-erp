<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.test',
            'phone' => '+1 555 0199', 'gender' => 'female', 'date_of_birth' => '1990-05-10',
            'joining_date' => '2024-01-15', 'employment_type' => 'full_time', 'salary' => '4200.50', 'status' => 'active',
        ];
    }

    public function test_hr_can_create_employee_with_login_and_photo(): void
    {
        Storage::fake('public');
        $hr = $this->makeUser('hr_manager');
        $dept = $this->makeDepartment('Finance');

        $response = $this->actingAs($hr)->post('/employees', $this->payload([
            'department_id' => $dept->id,
            'profile_photo' => UploadedFile::fake()->image('me.jpg'),
            'create_account' => '1',
            'account_role_id' => Role::where('slug', 'employee')->value('id'),
            'account_password' => 'welcome-123',
        ]));

        $employee = Employee::where('email', 'jane@example.test')->firstOrFail();
        $response->assertRedirect(route('employees.show', $employee));
        $this->assertNotNull($employee->user);
        $this->assertStringStartsWith('EMP-', $employee->employee_code);
        $this->assertSame('4200.50', $employee->salary);
        Storage::disk('public')->assertExists($employee->profile_photo);
        $this->assertDatabaseHas('activity_logs', ['module' => 'employees', 'action' => 'created', 'record_id' => $employee->id]);

        $this->post('/logout');
        $this->post('/login', ['email' => 'jane@example.test', 'password' => 'welcome-123'])->assertRedirect('/');
    }

    public function test_validation_errors_are_reported(): void
    {
        $hr = $this->makeUser('hr_manager');
        $this->actingAs($hr)->post('/employees', $this->payload(['email' => 'not-an-email', 'salary' => 'abc', 'first_name' => '', 'joining_date' => 'nope']))
            ->assertSessionHasErrors(['email', 'salary', 'first_name', 'joining_date']);
        $this->assertSame(1, Employee::count()); // only HR's own record
    }

    public function test_hr_cannot_grant_admin_role_through_employee_form(): void
    {
        $hr = $this->makeUser('hr_manager');
        $this->actingAs($hr)->post('/employees', $this->payload([
            'create_account' => '1', 'account_role_id' => Role::where('slug', 'admin')->value('id'), 'account_password' => 'welcome-123',
        ]))->assertSessionHasErrors('account_role_id');
    }

    public function test_employee_list_search_filter_and_sort(): void
    {
        $hr = $this->makeUser('hr_manager');
        $this->makeEmployee(['first_name' => 'Zed', 'last_name' => 'Alpha', 'status' => 'resigned']);
        $this->makeEmployee(['first_name' => 'Amy', 'last_name' => 'Beta']);

        $this->actingAs($hr)->get('/employees?q=Zed')->assertOk()->assertSee('Zed Alpha')->assertDontSee('Amy Beta');
        $this->actingAs($hr)->get('/employees?status=resigned')->assertSee('Zed Alpha')->assertDontSee('Amy Beta');
        $this->actingAs($hr)->get('/employees?sort=salary&direction=desc')->assertOk();
        $this->actingAs($hr)->get('/employees?sort=password')->assertOk(); // invalid sort is ignored
    }

    public function test_update_and_delete_employee(): void
    {
        $admin = $this->makeUser('admin');
        $employee = $this->makeEmployee();

        $this->actingAs($admin)->put(route('employees.update', $employee), $this->payload(['email' => $employee->email, 'first_name' => 'Renamed']))
            ->assertRedirect(route('employees.show', $employee));
        $this->assertSame('Renamed', $employee->fresh()->first_name);

        $this->actingAs($admin)->delete(route('employees.destroy', $employee))->assertRedirect('/employees');
        $this->assertSoftDeleted($employee);
    }

    public function test_deactivate_disables_login(): void
    {
        $admin = $this->makeUser('admin');
        $worker = $this->makeUser('employee');

        $this->actingAs($admin)->post(route('employees.deactivate', $worker->employee))->assertSessionHas('success');
        $this->assertSame('inactive', $worker->employee->fresh()->status);
        $this->assertSame('disabled', $worker->fresh()->status);
    }

    public function test_profile_tabs_render_for_hr(): void
    {
        $hr = $this->makeUser('hr_manager');
        $employee = $this->makeEmployee();
        $this->actingAs($hr)->get(route('employees.show', $employee))
            ->assertOk()->assertSee('Overview')->assertSee('Attendance')->assertSee('Payroll')->assertSee('Documents')->assertSee('Activity');
    }

    public function test_departments_crud_and_delete_guard(): void
    {
        $hr = $this->makeUser('hr_manager');
        $manager = $this->makeEmployee();

        $this->actingAs($hr)->post('/departments', ['name' => 'Legal', 'code' => 'LEG', 'status' => 'active', 'manager_id' => $manager->id])->assertRedirect();
        $dept = \App\Models\Department::where('name', 'Legal')->firstOrFail();
        $this->assertTrue($dept->manager->is($manager));

        $this->actingAs($hr)->post('/departments', ['name' => 'Legal', 'status' => 'active'])->assertSessionHasErrors('name');

        $manager->update(['department_id' => $dept->id]);
        $this->actingAs($hr)->delete(route('departments.destroy', $dept))->assertSessionHas('error');
        $manager->update(['department_id' => null]);
        $this->actingAs($hr)->delete(route('departments.destroy', $dept))->assertRedirect('/departments');

        $this->actingAs($hr)->post('/designations', ['name' => 'Architect', 'status' => 'active'])->assertRedirect('/designations');
        $this->assertDatabaseHas('designations', ['name' => 'Architect']);
    }
}
