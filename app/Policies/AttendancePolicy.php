<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermission('attendance.manage');
    }

    public function update(User $user, Attendance $attendance): bool
    {
        return $user->hasPermission('attendance.manage');
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->hasPermission('attendance.manage');
    }
}
