<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attendance = $this->route('attendance');

        return $attendance
            ? $this->user()->can('update', $attendance)
            : $this->user()->can('create', Attendance::class);
    }

    public function rules(): array
    {
        $attendance = $this->route('attendance');

        return [
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->whereNull('deleted_at'),
                // One record per employee per day.
                Rule::unique('attendances')->where(fn ($q) => $q->whereDate('date', (string) $this->input('date')))->ignore($attendance?->id),
            ],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::in(Attendance::STATUSES)],
            'check_in' => ['nullable', 'required_unless:status,absent,leave', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.unique' => 'An attendance record already exists for this employee on that date.',
            'check_in.required_unless' => 'Check-in time is required unless the employee is absent or on leave.',
            'date.before_or_equal' => 'Attendance cannot be recorded for a future date.',
        ];
    }

    public function attributes(): array
    {
        return ['employee_id' => 'employee'];
    }
}
