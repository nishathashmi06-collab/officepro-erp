<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function index(): View
    {
        return view('expense-categories.index', [
            'categories' => ExpenseCategory::withCount('expenses')->withSum(['expenses' => fn ($q) => $q->where('status', 'approved')], 'amount')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('expense-categories.form', ['category' => new ExpenseCategory(['status' => 'active'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        ExpenseCategory::create($this->validated($request));

        return redirect()->route('expense-categories.index')->with('success', 'Category created.');
    }

    public function edit(ExpenseCategory $expenseCategory): View
    {
        return view('expense-categories.form', ['category' => $expenseCategory]);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $expenseCategory->update($this->validated($request, $expenseCategory));

        return redirect()->route('expense-categories.index')->with('success', 'Category updated.');
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        if ($expenseCategory->expenses()->withTrashed()->exists()) {
            return back()->with('error', 'This category has expenses. Set it to inactive instead.');
        }
        $expenseCategory->delete();

        return back()->with('success', 'Category deleted.');
    }

    private function validated(Request $request, ?ExpenseCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('expense_categories', 'name')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
