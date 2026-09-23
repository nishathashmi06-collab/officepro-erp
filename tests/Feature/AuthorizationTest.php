<?php

namespace Tests\Feature;

use App\Models\Payroll;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_cannot_reach_admin_modules(): void
    {
        $employee = $this->makeUser('employee');

        foreach (['/users', '/roles', '/settings', '/activity-logs', '/reports', '/assets', '/departments', '/employees/create', '/payroll/generate', '/expenses'] as $url) {
            $this->actingAs($employee)->get($url)->assertForbidden();
        }
    }

    public function test_employee_can_only_view_own_profile(): void
    {
        $me = $this->makeUser('employee');
        $other = $this->makeEmployee();

        $this->actingAs($me)->get('/employees')->assertRedirect(route('employees.show', $me->employee));
        $this->actingAs($me)->get(route('employees.show', $me->employee))->assertOk();
        $this->actingAs($me)->get(route('employees.show', $other))->assertForbidden();
        $this->actingAs($me)->put(route('employees.update', $other), [])->assertForbidden();
    }

    public function test_manager_sees_only_team_members(): void
    {
        $manager = $this->makeUser('manager');
        $dept = $this->makeDepartment('IT', $manager->employee);
        $manager->employee->update(['department_id' => $dept->id]);
        $teamMember = $this->makeEmployee(['department_id' => $dept->id]);
        $outsider = $this->makeEmployee(['department_id' => $this->makeDepartment('Sales')->id]);

        $this->actingAs($manager)->get(route('employees.show', $teamMember))->assertOk();
        $this->actingAs($manager)->get(route('employees.show', $outsider))->assertForbidden();
        $this->actingAs($manager)->get('/employees')->assertOk()->assertSee($teamMember->full_name)->assertDontSee($outsider->full_name);
    }

    public function test_payroll_is_private(): void
    {
        $me = $this->makeUser('employee');
        $other = $this->makeEmployee();
        $mine = Payroll::create(['employee_id' => $me->employee->id, 'period' => '2026-08-01', 'basic_salary' => 3000, 'status' => 'paid']);
        $draft = Payroll::create(['employee_id' => $me->employee->id, 'period' => '2026-09-01', 'basic_salary' => 3000, 'status' => 'draft']);
        $theirs = Payroll::create(['employee_id' => $other->id, 'period' => '2026-08-01', 'basic_salary' => 9000, 'status' => 'paid']);

        $this->actingAs($me)->get(route('payroll.show', $mine))->assertOk();
        $this->actingAs($me)->get(route('payroll.show', $draft))->assertForbidden();
        $this->actingAs($me)->get(route('payroll.show', $theirs))->assertForbidden();
        $this->actingAs($me)->get(route('payroll.slip', $theirs))->assertForbidden();
        $this->actingAs($me)->get('/payroll')->assertOk()->assertDontSee('9,000.00');
    }

    public function test_hr_can_manage_employees_but_not_settings(): void
    {
        $hr = $this->makeUser('hr_manager');
        $this->actingAs($hr)->get('/employees/create')->assertOk();
        $this->actingAs($hr)->get('/payroll/generate')->assertOk();
        $this->actingAs($hr)->get('/settings')->assertForbidden();
        $this->actingAs($hr)->get('/users')->assertForbidden();
    }

    public function test_admin_cannot_grant_admin_roles_or_edit_super_admins(): void
    {
        $admin = $this->makeUser('admin');
        $super = $this->makeUser('super_admin');

        $this->actingAs($admin)->post('/users', [
            'name' => 'Sneaky', 'email' => 'sneaky@example.test', 'role_id' => Role::where('slug', 'super_admin')->value('id'),
            'status' => 'active', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('role_id');

        $this->actingAs($admin)->get(route('users.edit', $super))->assertForbidden();
        $this->actingAs($admin)->delete(route('users.destroy', $super))->assertForbidden();
    }

    public function test_primary_super_admin_cannot_be_deleted_or_disabled(): void
    {
        $primary = $this->makeUser('super_admin');
        $primary->forceFill(['is_primary' => true])->save();
        $otherSuper = $this->makeUser('super_admin');

        $this->actingAs($otherSuper)->delete(route('users.destroy', $primary))->assertSessionHas('error');
        $this->actingAs($otherSuper)->post(route('users.toggle-status', $primary))->assertForbidden();
        $this->assertNotSoftDeleted($primary);
        $this->assertSame('active', $primary->fresh()->status);
    }

    public function test_role_permissions_can_be_changed_by_super_admin_and_take_effect(): void
    {
        $super = $this->makeUser('super_admin');
        $employee = $this->makeUser('employee');
        $role = Role::where('slug', 'employee')->first();
        $perm = \App\Models\Permission::where('slug', 'reports.view')->value('id');

        $this->actingAs($employee)->get('/reports')->assertForbidden();
        $this->actingAs($super)->put(route('roles.update', $role), ['permissions' => [$perm]])->assertSessionHas('success');
        $this->actingAs(User::find($employee->id))->get('/reports')->assertOk();
    }
}
