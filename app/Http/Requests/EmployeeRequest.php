<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee
            ? $this->user()->can('update', $employee)
            : $this->user()->can('create', Employee::class);
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $id = $employee?->id;

        return [
            'employee_code' => ['nullable', 'string', 'max:30', Rule::unique('employees', 'employee_code')->ignore($id)],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190', Rule::unique('employees', 'email')->ignore($id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'gender' => ['nullable', Rule::in(Employee::GENDERS)],
            'date_of_birth' => ['nullable', 'date', 'before:-15 years'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'joining_date' => ['required', 'date', 'before_or_equal:+1 year'],
            'employment_type' => ['required', Rule::in(Employee::EMPLOYMENT_TYPES)],
            'salary' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'status' => ['required', Rule::in(Employee::STATUSES)],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],

            // Optional login account
            'user_id' => [
                'nullable',
                Rule::exists('users', 'id'),
                Rule::unique('employees', 'user_id')->ignore($id),
            ],
            'create_account' => ['nullable', 'boolean'],
            'account_role_id' => [
                'required_if:create_account,1', 'nullable', 'exists:roles,id',
                function ($attribute, $value, $fail) {
                    $role = $value ? Role::find($value) : null;
                    if ($role && in_array($role->slug, ['super_admin', 'admin'], true) && ! $this->user()->isSuperAdmin()) {
                        $fail('Only a super admin can grant administrator roles.');
                    }
                },
            ],
            'account_password' => ['required_if:create_account,1', 'nullable', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'date_of_birth.before' => 'The employee must be at least 15 years old.',
            'phone.regex' => 'Enter a valid phone number (digits, spaces, +, - and brackets).',
            'emergency_contact_phone.regex' => 'Enter a valid phone number.',
            'account_role_id.required_if' => 'Choose a role for the new login account.',
            'account_password.required_if' => 'Set an initial password for the new login account.',
            'user_id.unique' => 'That user account is already linked to another employee.',
        ];
    }

    public function attributes(): array
    {
        return [
            'department_id' => 'department',
            'designation_id' => 'designation',
            'account_role_id' => 'role',
            'account_password' => 'password',
        ];
    }
}
