<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Salary Slip · {{ $payroll->employee->full_name }} · {{ $payroll->period_label }}</title>
<style>
    @page { margin: 28px 34px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { color: #1e293b; font-size: 11px; margin: 0; }
    .header { border-bottom: 3px solid #4f46e5; padding-bottom: 14px; margin-bottom: 18px; }
    .brand { color: #4f46e5; font-size: 10px; font-weight: bold; letter-spacing: 2px; }
    .company { font-size: 20px; font-weight: bold; margin: 4px 0 2px; }
    .muted { color: #64748b; }
    .title { text-align: right; }
    .title h1 { margin: 0; font-size: 18px; color: #0f172a; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 10px; font-weight: bold; background: #e0e7ff; color: #3730a3; }
    .badge.paid { background: #d1fae5; color: #047857; }
    table { width: 100%; border-collapse: collapse; }
    .info td { padding: 5px 0; vertical-align: top; }
    .info .label { color: #64748b; width: 32%; }
    .box { border: 1px solid #e2e8f0; border-radius: 8px; }
    .lines th { background: #f1f5f9; text-align: left; padding: 8px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #475569; }
    .lines td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
    .lines .amt { text-align: right; }
    .lines .total td { font-weight: bold; background: #f8fafc; border-bottom: 0; }
    .net { margin-top: 18px; background: #4f46e5; color: #fff; border-radius: 8px; padding: 14px 18px; }
    .net .amount { font-size: 22px; font-weight: bold; text-align: right; }
    .footer { margin-top: 30px; font-size: 9px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    .sign td { padding-top: 40px; width: 50%; }
    .sign span { border-top: 1px solid #94a3b8; padding-top: 4px; display: inline-block; width: 70%; color: #64748b; }
</style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="brand">OFFICEPRO</div>
                <div class="company">{{ setting('company_name') }}</div>
                <div class="muted">{{ setting('company_address') }}</div>
                <div class="muted">{{ setting('company_email') }} · {{ setting('company_phone') }}</div>
            </td>
            <td class="title">
                <h1>SALARY SLIP</h1>
                <div class="muted" style="margin:4px 0 6px">{{ $payroll->period_label }}</div>
                <span class="badge {{ $payroll->status === 'paid' ? 'paid' : '' }}">{{ strtoupper(label($payroll->status)) }}</span>
            </td>
        </tr>
    </table>

    <table class="info box" style="padding: 10px 14px; margin-bottom: 18px">
        <tr>
            <td style="width:50%; padding: 10px 14px">
                <table>
                    <tr><td class="label">Employee</td><td><strong>{{ $payroll->employee->full_name }}</strong></td></tr>
                    <tr><td class="label">Employee ID</td><td>{{ $payroll->employee->employee_code }}</td></tr>
                    <tr><td class="label">Department</td><td>{{ $payroll->employee->department?->name ?? '—' }}</td></tr>
                    <tr><td class="label">Designation</td><td>{{ $payroll->employee->designation?->name ?? '—' }}</td></tr>
                </table>
            </td>
            <td style="width:50%; padding: 10px 14px">
                <table>
                    <tr><td class="label">Salary month</td><td>{{ $payroll->period_label }}</td></tr>
                    <tr><td class="label">Joining date</td><td>{{ fmt_date($payroll->employee->joining_date) }}</td></tr>
                    <tr><td class="label">Payment</td><td>{{ $payroll->paid_at ? label($payroll->payment_method).' · '.$payroll->paid_at->format('M d, Y') : 'Pending' }}</td></tr>
                    <tr><td class="label">Slip no.</td><td>PS-{{ $payroll->period->format('Ym') }}-{{ str_pad((string) $payroll->id, 5, '0', STR_PAD_LEFT) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width:50%; vertical-align: top; padding-right: 8px">
                <table class="lines box">
                    <tr><th>Earnings</th><th class="amt" style="text-align:right">Amount</th></tr>
                    <tr><td>Basic Salary</td><td class="amt">{{ money($payroll->basic_salary) }}</td></tr>
                    <tr><td>Allowances</td><td class="amt">{{ money($payroll->allowances) }}</td></tr>
                    <tr><td>Overtime</td><td class="amt">{{ money($payroll->overtime) }}</td></tr>
                    <tr><td>Bonus</td><td class="amt">{{ money($payroll->bonus) }}</td></tr>
                    <tr class="total"><td>Gross Salary</td><td class="amt">{{ money($payroll->gross_salary) }}</td></tr>
                </table>
            </td>
            <td style="width:50%; vertical-align: top; padding-left: 8px">
                <table class="lines box">
                    <tr><th>Deductions</th><th class="amt" style="text-align:right">Amount</th></tr>
                    <tr><td>Deductions</td><td class="amt">{{ money($payroll->deductions) }}</td></tr>
                    <tr><td>Tax</td><td class="amt">{{ money($payroll->tax) }}</td></tr>
                    <tr><td>Other Deductions</td><td class="amt">{{ money($payroll->other_deductions) }}</td></tr>
                    <tr><td>&nbsp;</td><td></td></tr>
                    <tr class="total"><td>Total Deductions</td><td class="amt">{{ money($payroll->total_deductions) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="net">
        <tr>
            <td><div style="font-size:10px; letter-spacing:1px">NET SALARY</div><div style="font-size:10px; opacity:.8">Gross − Deductions − Tax − Other deductions</div></td>
            <td class="amount">{{ money($payroll->net_salary) }} <span style="font-size:11px">{{ setting('currency') }}</span></td>
        </tr>
    </table>

    <table class="sign">
        <tr><td><span>Employer signature</span></td><td style="text-align:right"><span>Employee signature</span></td></tr>
    </table>

    <div class="footer">
        This is a computer-generated salary slip from OfficePro and does not require a physical signature.<br>
        Generated {{ now()->format('M d, Y h:i A') }} · {{ setting('company_website') }}
    </div>
</body>
</html>
