<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target ? $this->user()->can('update', $target) : $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($target?->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'role_id' => [
                'required', 'exists:roles,id',
                function ($attribute, $value, $fail) use ($target) {
                    $role = Role::find($value);
                    if ($role && in_array($role->slug, ['super_admin', 'admin'], true) && ! $this->user()->isSuperAdmin()) {
                        $fail('Only a super admin can assign administrator roles.');
                    }
                    if ($target?->is_primary && $role?->slug !== 'super_admin') {
                        $fail('The primary super admin must keep the Super Admin role.');
                    }
                },
            ],
            'status' => [
                'required', Rule::in(User::STATUSES),
                function ($attribute, $value, $fail) use ($target) {
                    if ($value !== 'active' && ($target?->is_primary || $target?->is($this->user()))) {
                        $fail('This account cannot be disabled.');
                    }
                },
            ],
            'password' => [$target ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return ['role_id' => 'role'];
    }
}
