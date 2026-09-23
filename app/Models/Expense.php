<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    public const STATUSES = ['pending', 'approved', 'rejected'];

    public const PAYMENT_METHODS = ['cash', 'bank_transfer', 'credit_card', 'debit_card', 'cheque', 'other'];

    protected $fillable = [
        'title', 'expense_category_id', 'amount', 'date', 'payment_method', 'description',
        'receipt', 'receipt_name', 'created_by', 'status', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasPermission('expenses.view_all')) {
            return $query;
        }

        return $query->where('expenses.created_by', $user->id);
    }
}
