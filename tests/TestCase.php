<?php

namespace Tests;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected bool $seedReferenceData = true;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::flush();

        if ($this->seedReferenceData && in_array(\Illuminate\Foundation\Testing\RefreshDatabase::class, class_uses_recursive($this))) {
            $this->seed([RolePermissionSeeder::class, ReferenceDataSeeder::class]);
        }
    }

    /** Create a user with the given role and (optionally) a linked employee record. */
    protected function makeUser(string $role, array $employee = [], bool $withEmployee = true): User
    {
        static $n = 0;
        $n++;

        $user = User::factory()->create([
            'role_id' => Role::where('slug', $role)->value('id'),
            'is_primary' => false,
        ]);

        if ($withEmployee) {
            $this->makeEmployee(['user_id' => $user->id, 'email' => $user->email] + $employee);
        }

        return $user->fresh();
    }

    protected function makeEmployee(array $attributes = []): Employee
    {
        static $n = 0;
        $n++;

        return Employee::create($attributes + [
            'employee_code' => 'T-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'first_name' => 'Test',
            'last_name' => 'Person'.$n,
            'email' => "person{$n}@example.test",
            'joining_date' => now()->subYear()->toDateString(),
            'employment_type' => 'full_time',
            'salary' => 3000,
            'status' => 'active',
            'designation_id' => Designation::value('id'),
        ]);
    }

    protected function makeDepartment(string $name, ?Employee $manager = null): Department
    {
        return Department::create(['name' => $name, 'status' => 'active', 'manager_id' => $manager?->id]);
    }
}
