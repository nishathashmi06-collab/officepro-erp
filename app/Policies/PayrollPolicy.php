<?php

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;

class PayrollPolicy
{
    public function view(User $user, Payroll $payroll): bool
    {
        if ($user->hasPermission('payroll.view_all')) {
            return true;
        }

        return $user->employee?->id == $payroll->employee_id
            && in_array($payroll->status, ['approved', 'paid'], true);
    }

    public function update(User $user, Payroll $payroll): bool
    {
        return $user->hasPermission('payroll.manage') && $payroll->status !== 'paid';
    }

    public function delete(User $user, Payroll $payroll): bool
    {
        return $user->hasPermission('payroll.manage') && in_array($payroll->status, ['draft', 'pending'], true);
    }

    public function approve(User $user, Payroll $payroll): bool
    {
        return $user->hasPermission('payroll.manage') && in_array($payroll->status, ['draft', 'pending'], true);
    }

    public function markPaid(User $user, Payroll $payroll): bool
    {
        return $user->hasPermission('payroll.manage') && $payroll->status === 'approved';
    }
}
