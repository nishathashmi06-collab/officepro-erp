<?php

namespace App\Support;

/**
 * Central registry of permissions and the default role → permission map.
 * Used by the seeder and the roles & permissions screen.
 */
class Permissions
{
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_HR = 'hr_manager';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_EMPLOYEE = 'employee';

    /** @return array<string, array{name:string, module:string}> */
    public static function all(): array
    {
        return [
            'users.manage' => ['name' => 'Manage user accounts', 'module' => 'System'],
            'roles.manage' => ['name' => 'Manage roles & permissions', 'module' => 'System'],
            'settings.manage' => ['name' => 'Manage system settings', 'module' => 'System'],
            'activity_logs.view' => ['name' => 'View activity logs', 'module' => 'System'],

            'employees.view_all' => ['name' => 'View all employees', 'module' => 'Employees'],
            'employees.view_team' => ['name' => 'View team employees', 'module' => 'Employees'],
            'employees.create' => ['name' => 'Create employees', 'module' => 'Employees'],
            'employees.edit' => ['name' => 'Edit employees', 'module' => 'Employees'],
            'employees.delete' => ['name' => 'Delete / deactivate employees', 'module' => 'Employees'],
            'departments.manage' => ['name' => 'Manage departments', 'module' => 'Employees'],
            'designations.manage' => ['name' => 'Manage designations', 'module' => 'Employees'],

            'attendance.view_all' => ['name' => 'View all attendance', 'module' => 'Attendance'],
            'attendance.view_team' => ['name' => 'View team attendance', 'module' => 'Attendance'],
            'attendance.manage' => ['name' => 'Create / edit attendance records', 'module' => 'Attendance'],

            'leaves.view_all' => ['name' => 'View all leave requests', 'module' => 'Leave'],
            'leaves.view_team' => ['name' => 'View team leave requests', 'module' => 'Leave'],
            'leaves.approve' => ['name' => 'Approve / reject leave requests', 'module' => 'Leave'],
            'leave_types.manage' => ['name' => 'Manage leave types', 'module' => 'Leave'],

            'tasks.view_all' => ['name' => 'View all tasks', 'module' => 'Tasks'],
            'tasks.create' => ['name' => 'Create & assign tasks', 'module' => 'Tasks'],
            'tasks.manage_all' => ['name' => 'Edit / delete any task', 'module' => 'Tasks'],

            'payroll.view_all' => ['name' => 'View all payroll records', 'module' => 'Payroll'],
            'payroll.manage' => ['name' => 'Generate, edit, approve & pay payroll', 'module' => 'Payroll'],

            'expenses.view_all' => ['name' => 'View all expenses', 'module' => 'Expenses'],
            'expenses.create' => ['name' => 'Submit expenses', 'module' => 'Expenses'],
            'expenses.approve' => ['name' => 'Approve / reject expenses', 'module' => 'Expenses'],
            'expenses.manage' => ['name' => 'Edit / delete any expense & categories', 'module' => 'Expenses'],

            'assets.view' => ['name' => 'View company assets', 'module' => 'Assets'],
            'assets.manage' => ['name' => 'Manage, assign & return assets', 'module' => 'Assets'],

            'documents.view_all' => ['name' => 'View all documents', 'module' => 'Documents'],
            'documents.manage' => ['name' => 'Upload & delete documents', 'module' => 'Documents'],

            'reports.view' => ['name' => 'View & export reports', 'module' => 'Reports'],
        ];
    }

    /** @return array<string, array{name:string, description:string, permissions:array<int,string>}> */
    public static function roles(): array
    {
        $all = array_keys(self::all());

        return [
            self::ROLE_SUPER_ADMIN => [
                'name' => 'Super Admin',
                'description' => 'Full system access including roles, permissions and settings.',
                'permissions' => $all,
            ],
            self::ROLE_ADMIN => [
                'name' => 'Admin',
                'description' => 'Full operational access to all business modules.',
                'permissions' => array_values(array_diff($all, ['roles.manage', 'settings.manage'])),
            ],
            self::ROLE_HR => [
                'name' => 'HR Manager',
                'description' => 'Employees, departments, attendance, leave, payroll, documents and reports.',
                'permissions' => [
                    'employees.view_all', 'employees.view_team', 'employees.create', 'employees.edit', 'employees.delete',
                    'departments.manage', 'designations.manage',
                    'attendance.view_all', 'attendance.view_team', 'attendance.manage',
                    'leaves.view_all', 'leaves.view_team', 'leaves.approve', 'leave_types.manage',
                    'payroll.view_all', 'payroll.manage',
                    'documents.view_all', 'documents.manage',
                    'reports.view', 'expenses.create',
                ],
            ],
            self::ROLE_MANAGER => [
                'name' => 'Manager',
                'description' => 'Manages a team: team attendance, leave approval and task assignment.',
                'permissions' => [
                    'employees.view_team', 'attendance.view_team',
                    'leaves.view_team', 'leaves.approve',
                    'tasks.create', 'expenses.create',
                ],
            ],
            self::ROLE_EMPLOYEE => [
                'name' => 'Employee',
                'description' => 'Self-service access to own profile, attendance, leave, tasks, payslips and documents.',
                'permissions' => [],
            ],
        ];
    }
}
