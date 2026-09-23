<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->employee !== null || $this->user()->hasPermission('leaves.view_all');
    }

    public function rules(): array
    {
        $type = LeaveType::find($this->input('leave_type_id'));

        return [
            // HR/admin may file leave on behalf of an employee.
            'employee_id' => [
                Rule::requiredIf(fn () => $this->user()->employee === null),
                'nullable',
                Rule::exists('employees', 'id')->whereNull('deleted_at'),
            ],
            'leave_type_id' => ['required', Rule::exists('leave_types', 'id')->where('status', 'active')],
            'start_date' => ['required', 'date', 'after_or_equal:'.today()->subDays(30)->toDateString()],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'attachment' => [
                $type?->requires_attachment ? 'required' : 'nullable',
                'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'Leave cannot be backdated by more than 30 days.',
            'attachment.required' => 'This leave type requires a supporting document.',
        ];
    }

    public function attributes(): array
    {
        return ['leave_type_id' => 'leave type', 'employee_id' => 'employee'];
    }

    public function targetEmployee(): Employee
    {
        if ($this->filled('employee_id') && $this->user()->hasPermission('leaves.view_all')) {
            return Employee::findOrFail($this->input('employee_id'));
        }

        return $this->user()->employee;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $employee = $this->targetEmployee();
                $start = $this->input('start_date');
                $end = $this->input('end_date');

                if (Carbon::parse($start)->year !== Carbon::parse($end)->year) {
                    $validator->errors()->add('end_date', 'A leave request cannot span two calendar years. Please submit two requests.');

                    return;
                }

                $days = Leave::countDays($start, $end);
                if ($days === 0) {
                    $validator->errors()->add('end_date', 'The selected dates contain no working days.');

                    return;
                }

                if (Leave::overlapping($employee->id, $start, $end)->exists()) {
                    $validator->errors()->add('start_date', 'These dates overlap with an existing pending or approved leave request.');

                    return;
                }

                $type = LeaveType::find($this->input('leave_type_id'));
                $remaining = app(LeaveBalanceService::class)->remaining($employee, $type, Carbon::parse($start)->year);
                if ($remaining !== null && $days > $remaining) {
                    $validator->errors()->add('leave_type_id', "Insufficient {$type->name} balance: {$remaining} day(s) remaining, {$days} requested.");
                }
            },
        ];
    }
}
