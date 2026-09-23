<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    public function view(User $user, User $target): bool
    {
        return $user->hasPermission('users.manage') || $user->is($target);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    /** Admin-level accounts can only be managed by a super admin. */
    public function update(User $user, User $target): bool
    {
        if (! $user->hasPermission('users.manage')) {
            return false;
        }

        if ($target->is_primary && ! $user->is($target)) {
            return false;
        }

        return ! $target->isPrivileged() || $user->isSuperAdmin();
    }

    public function delete(User $user, User $target): bool
    {
        return ! $target->is_primary
            && ! $user->is($target)
            && $this->update($user, $target);
    }

    public function toggleStatus(User $user, User $target): bool
    {
        return $this->delete($user, $target);
    }
}
