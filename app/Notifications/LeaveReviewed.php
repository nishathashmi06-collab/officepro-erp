<?php

namespace App\Notifications;

use App\Models\Leave;

class LeaveReviewed extends AppNotification
{
    public function __construct(public Leave $leave)
    {
    }

    public function type(): string
    {
        return 'leave_reviewed';
    }

    public function title(): string
    {
        return 'Leave request '.$this->leave->status;
    }

    public function message(): string
    {
        return sprintf(
            'Your %s request for %s → %s was %s.',
            $this->leave->leaveType->name,
            $this->leave->start_date->format('M d'),
            $this->leave->end_date->format('M d'),
            $this->leave->status
        ).($this->leave->review_note ? ' Note: '.$this->leave->review_note : '');
    }

    public function url(): string
    {
        return route('leaves.show', $this->leave);
    }

    public function icon(): string
    {
        return $this->leave->status === 'approved' ? 'bi-check-circle' : 'bi-x-circle';
    }

    public function color(): string
    {
        return $this->leave->status === 'approved' ? 'success' : 'danger';
    }
}
