<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Notifications\PayrollGenerated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_payroll_creates_drafts_and_deducts_unpaid_leave(): void
    {
        $hr = $this->makeUser('hr_manager', ['salary' => 4400]);
        $worker = $this->makeEmployee(['salary' => 2200]);
        // 2026-09 has 22 working days; one unpaid day = 100
        Leave::create(['employee_id' => $worker->id, 'leave_type_id' => LeaveType::where('code', 'UL')->value('id'), 'start_date' => '2026-09-14', 'end_date' => '2026-09-14', 'days' => 1, 'reason' => 'x', 'status' => 'approved']);

        $this->actingAs($hr)->post('/payroll/generate', ['month' => '2026-09', 'allowance_percent' => 10, 'tax_percent' => 5])
            ->assertRedirect(route('payroll.index', ['month' => '2026-09']));

        $this->assertSame(2, Payroll::count());
        $p = Payroll::where('employee_id', $worker->id)->first();
        $this->assertSame('draft', $p->status);
        $this->assertEquals(220, (float) $p->allowances);
        $this->assertEquals(100, (float) $p->deductions);
        $this->assertEquals(121, (float) $p->tax);          // 5% of 2420
        $this->assertEquals(2420, (float) $p->gross_salary);
        $this->assertEquals(2199, (float) $p->net_salary);   // 2420 - 100 - 121

        // Re-running skips existing records
        $this->actingAs($hr)->post('/payroll/generate', ['month' => '2026-09', 'allowance_percent' => 10, 'tax_percent' => 5]);
        $this->assertSame(2, Payroll::count());
    }

    public function test_edit_recalculates_totals_server_side(): void
    {
        $hr = $this->makeUser('hr_manager');
        $p = Payroll::create(['employee_id' => $this->makeEmployee()->id, 'period' => '2026-09-01', 'basic_salary' => 1000, 'status' => 'draft']);

        $this->actingAs($hr)->put(route('payroll.update', $p), [
            'basic_salary' => 3000, 'allowances' => 300, 'overtime' => 150, 'bonus' => 50,
            'deductions' => 100, 'tax' => 200, 'other_deductions' => 25, 'status' => 'pending',
            'gross_salary' => 999999, 'net_salary' => 999999, // ignored
        ])->assertRedirect(route('payroll.show', $p));

        $p->refresh();
        $this->assertEquals(3500, (float) $p->gross_salary);
        $this->assertEquals(3175, (float) $p->net_salary);
    }

    public function test_deductions_cannot_exceed_gross(): void
    {
        $hr = $this->makeUser('hr_manager');
        $p = Payroll::create(['employee_id' => $this->makeEmployee()->id, 'period' => '2026-09-01', 'basic_salary' => 1000, 'status' => 'draft']);
        $this->actingAs($hr)->put(route('payroll.update', $p), ['basic_salary' => 100, 'allowances' => 0, 'overtime' => 0, 'bonus' => 0, 'deductions' => 500, 'tax' => 0, 'other_deductions' => 0, 'status' => 'draft'])
            ->assertSessionHasErrors('deductions');
    }

    public function test_approve_pay_and_employee_access_to_slip(): void
    {
        Notification::fake();
        $hr = $this->makeUser('hr_manager');
        $worker = $this->makeUser('employee');
        $p = Payroll::create(['employee_id' => $worker->employee->id, 'period' => '2026-09-01', 'basic_salary' => 3000, 'status' => 'draft']);

        $this->actingAs($worker)->get(route('payroll.show', $p))->assertForbidden();

        $this->actingAs($hr)->post(route('payroll.pay', $p), ['payment_method' => 'cash'])->assertForbidden(); // must approve first
        $this->actingAs($hr)->post(route('payroll.approve', $p))->assertSessionHas('success');
        Notification::assertSentTo($worker, PayrollGenerated::class);

        $this->actingAs($hr)->post(route('payroll.pay', $p), ['payment_method' => 'bank_transfer'])->assertSessionHas('success');
        $this->assertSame('paid', $p->fresh()->status);
        $this->actingAs($hr)->get(route('payroll.edit', $p))->assertForbidden(); // paid is locked

        $this->actingAs($worker)->get(route('payroll.show', $p))->assertOk();
        $response = $this->actingAs($worker)->get(route('payroll.slip', [$p, 'download' => 1]));
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_bulk_approve(): void
    {
        $hr = $this->makeUser('hr_manager');
        foreach (range(1, 3) as $i) {
            Payroll::create(['employee_id' => $this->makeEmployee()->id, 'period' => '2026-09-01', 'basic_salary' => 1000, 'status' => 'draft']);
        }
        $this->actingAs($hr)->post('/payroll/bulk-approve', ['month' => '2026-09'])->assertSessionHas('success');
        $this->assertSame(3, Payroll::where('status', 'approved')->count());
    }

    public function test_manager_cannot_manage_payroll(): void
    {
        $manager = $this->makeUser('manager');
        $this->actingAs($manager)->post('/payroll/generate', ['month' => '2026-09', 'allowance_percent' => 0, 'tax_percent' => 0])->assertForbidden();
    }
}
