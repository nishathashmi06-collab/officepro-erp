<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;

class LeavePolicy
{
    public function view(User $user, Leave $leave): bool
    {
        if ($user->hasPermission('leaves.view_all') || $user->employee?->id == $leave->employee_id) {
            return true;
        }

        return $user->hasPermission('leaves.view_team')
            && in_array($leave->employee_id, $user->teamEmployeeIds());
    }

    /** Reviewers cannot approve their own leave; managers only their team's. */
    public function review(User $user, Leave $leave): bool
    {
        if ($leave->status !== 'pending' || ! $user->hasPermission('leaves.approve')) {
            return false;
        }

        if ($user->employee?->id == $leave->employee_id && ! $user->isSuperAdmin()) {
            return false;
        }

        return $user->hasPermission('leaves.view_all')
            || in_array($leave->employee_id, $user->teamEmployeeIds());
    }

    public function cancel(User $user, Leave $leave): bool
    {
        if ($user->employee?->id != $leave->employee_id) {
            return false;
        }

        return $leave->status === 'pending'
            || ($leave->status === 'approved' && $leave->start_date->isFuture());
    }
}
