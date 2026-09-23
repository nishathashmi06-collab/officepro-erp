<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('expenses.view_all') || $user->hasPermission('expenses.create');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->hasPermission('expenses.view_all') || $expense->created_by == $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('expenses.create');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->hasPermission('expenses.manage')
            || ($expense->created_by == $user->id && $expense->status === 'pending');
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $this->update($user, $expense);
    }

    /** Nobody but a super admin approves their own expense claim. */
    public function review(User $user, Expense $expense): bool
    {
        return $expense->status === 'pending'
            && $user->hasPermission('expenses.approve')
            && ($expense->created_by != $user->id || $user->isSuperAdmin());
    }
}
