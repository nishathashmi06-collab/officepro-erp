<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Expense::class);
        $user = $request->user();

        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);

        $query = Expense::visibleTo($user)
            ->with(['category', 'creator'])
            ->when($request->query('q'), fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->when($request->query('category'), fn ($q, $v) => $q->where('expense_category_id', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('from'), fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($request->query('to'), fn ($q, $v) => $q->whereDate('date', '<=', $v));

        $summary = (clone $query)->reorder()->selectRaw('status, COUNT(*) as records, SUM(amount) as total')->groupBy('status')->get()->keyBy('status');

        $monthStart = today()->startOfMonth();
        $byCategory = Expense::visibleTo($user)->where('status', 'approved')
            ->whereBetween('date', [$monthStart->toDateString(), today()->endOfMonth()->toDateString()])
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->selectRaw('expense_categories.name as category, SUM(expenses.amount) as total')
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get();

        return view('expenses.index', [
            'expenses' => $query->latest('date')->latest('id')->paginate(15)->withQueryString(),
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'summary' => $summary,
            'byCategory' => $byCategory,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Expense::class);

        return view('expenses.form', [
            'expense' => new Expense(['date' => today(), 'payment_method' => 'bank_transfer']),
            'categories' => ExpenseCategory::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['receipt', 'remove_receipt']);
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'pending';

        if ($request->hasFile('receipt')) {
            $data['receipt'] = $request->file('receipt')->store('receipts', 'local');
            $data['receipt_name'] = $request->file('receipt')->getClientOriginalName();
        }

        $expense = Expense::create($data);
        activity('created', 'expenses', "{$request->user()->name} submitted expense \"{$expense->title}\" (".money($expense->amount).')', $expense);

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense submitted for approval.');
    }

    public function show(Expense $expense): View
    {
        $this->authorize('view', $expense);
        $expense->load(['category', 'creator', 'reviewer']);

        return view('expenses.show', compact('expense'));
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('update', $expense);

        return view('expenses.form', [
            'expense' => $expense,
            'categories' => ExpenseCategory::where('status', 'active')->orWhere('id', $expense->expense_category_id)->orderBy('name')->get(),
        ]);
    }

    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $data = $request->safe()->except(['receipt', 'remove_receipt']);

        if ($request->hasFile('receipt')) {
            $this->deleteReceipt($expense);
            $data['receipt'] = $request->file('receipt')->store('receipts', 'local');
            $data['receipt_name'] = $request->file('receipt')->getClientOriginalName();
        } elseif ($request->boolean('remove_receipt')) {
            $this->deleteReceipt($expense);
            $data['receipt'] = $data['receipt_name'] = null;
        }

        $expense->update($data);
        activity('updated', 'expenses', "Updated expense \"{$expense->title}\"", $expense);

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);
        $expense->delete();
        activity('deleted', 'expenses', "Deleted expense \"{$expense->title}\"", $expense);

        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }

    public function approve(Request $request, Expense $expense): RedirectResponse
    {
        return $this->review($request, $expense, 'approved');
    }

    public function reject(Request $request, Expense $expense): RedirectResponse
    {
        return $this->review($request, $expense, 'rejected');
    }

    public function receipt(Expense $expense): StreamedResponse
    {
        $this->authorize('view', $expense);
        abort_unless($expense->receipt && Storage::disk('local')->exists($expense->receipt), 404);

        return Storage::disk('local')->download($expense->receipt, $expense->receipt_name ?: basename($expense->receipt));
    }

    private function review(Request $request, Expense $expense, string $status): RedirectResponse
    {
        $this->authorize('review', $expense);
        $data = $request->validate(['review_note' => [$status === 'rejected' ? 'required' : 'nullable', 'string', 'max:500']]);

        $expense->update([
            'status' => $status,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $data['review_note'] ?? null,
        ]);

        activity($status, 'expenses', "{$request->user()->name} {$status} expense \"{$expense->title}\"", $expense);

        return back()->with('success', "Expense {$status}.");
    }

    private function deleteReceipt(Expense $expense): void
    {
        if ($expense->receipt) {
            Storage::disk('local')->delete($expense->receipt);
        }
    }
}
