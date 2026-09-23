<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('employees.view_all') || $user->hasPermission('employees.view_team');
    }

    public function view(User $user, Employee $employee): bool
    {
        if ($user->hasPermission('employees.view_all') || $user->employee?->is($employee)) {
            return true;
        }

        return $user->hasPermission('employees.view_team')
            && in_array($employee->id, $user->teamEmployeeIds());
    }

    /** Salary, payroll and private documents: HR/payroll staff or the employee. */
    public function viewSensitive(User $user, Employee $employee): bool
    {
        return $user->employee?->is($employee)
            || $user->hasPermission('payroll.view_all')
            || $user->hasPermission('employees.edit');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('employees.create');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasPermission('employees.edit');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasPermission('employees.delete') && ! $user->employee?->is($employee);
    }
}
