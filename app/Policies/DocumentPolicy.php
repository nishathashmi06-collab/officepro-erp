<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        if ($user->hasPermission('documents.view_all')) {
            return true;
        }

        if (! $document->employee_visible) {
            return false;
        }

        return $document->employee_id == null || $document->employee_id == $user->employee?->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('documents.manage');
    }

    public function update(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.manage');
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.manage');
    }
}
