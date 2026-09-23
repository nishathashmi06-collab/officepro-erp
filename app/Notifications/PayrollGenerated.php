<?php

namespace App\Notifications;

use App\Models\Payroll;

class PayrollGenerated extends AppNotification
{
    public function __construct(public Payroll $payroll, public string $event = 'approved')
    {
    }

    public function type(): string
    {
        return 'payroll_generated';
    }

    public function title(): string
    {
        return $this->event === 'paid' ? 'Salary paid' : 'Salary slip available';
    }

    public function message(): string
    {
        return sprintf(
            'Your salary for %s (net %s) has been %s.',
            $this->payroll->period_label,
            money($this->payroll->net_salary),
            $this->event
        );
    }

    public function url(): string
    {
        return route('payroll.show', $this->payroll);
    }

    public function icon(): string
    {
        return 'bi-cash-stack';
    }

    public function color(): string
    {
        return 'success';
    }
}
