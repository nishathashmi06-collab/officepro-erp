<?php

namespace App\Http\Requests;

use App\Models\Payroll;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('payroll'));
    }

    public function rules(): array
    {
        $money = ['required', 'numeric', 'min:0', 'max:99999999'];

        return [
            'basic_salary' => $money,
            'allowances' => $money,
            'overtime' => $money,
            'bonus' => $money,
            'deductions' => $money,
            'tax' => $money,
            'other_deductions' => $money,
            'status' => ['required', Rule::in(['draft', 'pending', 'approved'])],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $totals = Payroll::calculate($this->only(Payroll::AMOUNT_FIELDS));
                if ($totals['net'] < 0) {
                    $validator->errors()->add('deductions', 'Total deductions cannot exceed the gross salary.');
                }
            },
        ];
    }
}
