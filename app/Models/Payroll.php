<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    public const STATUSES = ['draft', 'pending', 'approved', 'paid'];

    public const AMOUNT_FIELDS = ['basic_salary', 'allowances', 'overtime', 'bonus', 'deductions', 'tax', 'other_deductions'];

    protected $fillable = [
        'employee_id', 'period', 'basic_salary', 'allowances', 'overtime', 'bonus',
        'deductions', 'tax', 'other_deductions', 'gross_salary', 'net_salary',
        'status', 'payment_method', 'paid_at', 'notes', 'generated_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'period' => 'date',
        'paid_at' => 'datetime',
        'approved_at' => 'datetime',
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'overtime' => 'decimal:2',
        'bonus' => 'decimal:2',
        'deductions' => 'decimal:2',
        'tax' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Totals are always derived from the components, never trusted from input.
        static::saving(function (Payroll $payroll) {
            $totals = static::calculate($payroll->only(self::AMOUNT_FIELDS));
            $payroll->gross_salary = $totals['gross'];
            $payroll->net_salary = $totals['net'];
        });
    }

    /**
     * Gross = Basic + Allowances + Overtime + Bonus
     * Net   = Gross - Deductions - Tax - Other Deductions
     *
     * @return array{gross: float, net: float}
     */
    public static function calculate(array $values): array
    {
        $v = fn (string $key) => round((float) ($values[$key] ?? 0), 2);

        $gross = $v('basic_salary') + $v('allowances') + $v('overtime') + $v('bonus');
        $net = $gross - $v('deductions') - $v('tax') - $v('other_deductions');

        return ['gross' => round($gross, 2), 'net' => round($net, 2)];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasPermission('payroll.view_all')) {
            return $query;
        }

        // Employees only ever see their own finalised salary records.
        return $query->where('payrolls.employee_id', $user->employee?->id ?? 0)
            ->whereIn('payrolls.status', ['approved', 'paid']);
    }

    public function getGrossEarningsAttribute(): float
    {
        return (float) $this->gross_salary;
    }

    public function getTotalDeductionsAttribute(): float
    {
        return round((float) $this->deductions + (float) $this->tax + (float) $this->other_deductions, 2);
    }

    public function getPeriodLabelAttribute(): string
    {
        return $this->period?->format('F Y') ?? '';
    }
}
