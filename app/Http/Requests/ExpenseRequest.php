<?php

namespace App\Http\Requests;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $expense = $this->route('expense');

        return $expense ? $this->user()->can('update', $expense) : $this->user()->can('create', Expense::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'expense_category_id' => ['required', Rule::exists('expense_categories', 'id')],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', Rule::in(Expense::PAYMENT_METHODS)],
            'description' => ['nullable', 'string', 'max:2000'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'remove_receipt' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['expense_category_id' => 'category'];
    }
}
