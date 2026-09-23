<?php

namespace App\Notifications;

use App\Models\Leave;

class LeaveRequested extends AppNotification
{
    public function __construct(public Leave $leave)
    {
    }

    public function type(): string
    {
        return 'leave_request';
    }

    public function title(): string
    {
        return 'New leave request';
    }

    public function message(): string
    {
        return sprintf(
            '%s requested %s (%s → %s, %s day(s)).',
            $this->leave->employee->full_name,
            $this->leave->leaveType->name,
            $this->leave->start_date->format('M d'),
            $this->leave->end_date->format('M d'),
            rtrim(rtrim((string) $this->leave->days, '0'), '.')
        );
    }

    public function url(): string
    {
        return route('leaves.show', $this->leave);
    }

    public function icon(): string
    {
        return 'bi-calendar-plus';
    }

    public function color(): string
    {
        return 'warning';
    }
}
