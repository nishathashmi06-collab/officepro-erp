<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('departments.manage');
    }

    public function rules(): array
    {
        $id = $this->route('department')?->id;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('departments', 'name')->ignore($id)],
            'code' => ['nullable', 'string', 'max:20', 'alpha_dash', Rule::unique('departments', 'code')->ignore($id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'manager_id' => ['nullable', Rule::exists('employees', 'id')->whereNull('deleted_at')],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function attributes(): array
    {
        return ['manager_id' => 'manager'];
    }
}
