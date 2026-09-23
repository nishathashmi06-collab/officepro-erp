<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Notifications\AssetAssigned;
use App\Notifications\DocumentExpiring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseAssetDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_with_receipt_upload_and_approval(): void
    {
        Storage::fake('local');
        $manager = $this->makeUser('manager');
        $admin = $this->makeUser('admin');

        $this->actingAs($manager)->post('/expenses', [
            'title' => 'Taxi to client', 'expense_category_id' => ExpenseCategory::where('name', 'Travel')->value('id'),
            'amount' => '45.90', 'date' => today()->toDateString(), 'payment_method' => 'cash',
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
        ])->assertRedirect();

        $expense = Expense::firstOrFail();
        $this->assertSame('pending', $expense->status);
        Storage::disk('local')->assertExists($expense->receipt);

        $this->actingAs($manager)->post(route('expenses.approve', $expense))->assertForbidden(); // no self-approval
        $this->actingAs($admin)->post(route('expenses.reject', $expense))->assertSessionHasErrors('review_note');
        $this->actingAs($admin)->post(route('expenses.approve', $expense))->assertSessionHas('success');
        $this->assertSame('approved', $expense->fresh()->status);

        $this->actingAs($manager)->get(route('expenses.receipt', $expense))->assertOk();
        $this->actingAs($manager)->get(route('expenses.edit', $expense))->assertForbidden(); // approved = locked for submitter
        $this->actingAs($this->makeUser('manager'))->get(route('expenses.receipt', $expense))->assertForbidden();
    }

    public function test_expense_validation_and_filters(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->post('/expenses', ['title' => '', 'amount' => '-5', 'date' => now()->addWeek()->toDateString(), 'payment_method' => 'gold', 'receipt' => UploadedFile::fake()->create('evil.exe', 10)])
            ->assertSessionHasErrors(['title', 'amount', 'date', 'payment_method', 'expense_category_id', 'receipt']);
        $this->actingAs($admin)->get('/expenses?from=2026-01-01&to=2026-12-31&status=approved&category=1')->assertOk();
    }

    public function test_asset_assign_return_and_maintenance(): void
    {
        Notification::fake();
        $admin = $this->makeUser('admin');
        $worker = $this->makeUser('employee');

        $this->actingAs($admin)->post('/assets', ['name' => 'ThinkPad', 'category' => 'laptop', 'serial_number' => 'SN-1', 'condition' => 'new', 'status' => 'available', 'purchase_price' => 1200])->assertRedirect();
        $asset = Asset::firstOrFail();
        $this->assertStringStartsWith('AST-', $asset->asset_code);

        $this->actingAs($admin)->post(route('assets.assign', $asset), ['employee_id' => $worker->employee->id, 'assigned_at' => today()->toDateString()])->assertSessionHas('success');
        $this->assertSame('assigned', $asset->fresh()->status);
        Notification::assertSentTo($worker, AssetAssigned::class);

        $this->actingAs($admin)->post(route('assets.assign', $asset), ['employee_id' => $worker->employee->id, 'assigned_at' => today()->toDateString()])->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('assets.destroy', $asset))->assertSessionHas('error');

        $this->actingAs($admin)->post(route('assets.return', $asset), ['returned_at' => today()->toDateString(), 'condition' => 'good'])->assertSessionHas('success');
        $asset->refresh();
        $this->assertSame('available', $asset->status);
        $this->assertNull($asset->employee_id);
        $this->assertNotNull($asset->assignments()->first()->returned_at);

        $this->actingAs($admin)->post(route('assets.maintenance.store', $asset), ['title' => 'Battery', 'started_at' => today()->toDateString(), 'cost' => 80])->assertSessionHas('success');
        $this->assertSame('maintenance', $asset->fresh()->status);
        $m = $asset->maintenances()->first();
        $this->actingAs($admin)->post(route('assets.maintenance.complete', [$asset, $m]), ['completed_at' => today()->toDateString(), 'condition' => 'good'])->assertSessionHas('success');
        $this->assertSame('available', $asset->fresh()->status);

        $this->actingAs($admin)->get(route('assets.show', $asset))->assertOk()->assertSee('Battery');
        $this->actingAs($worker)->get(route('assets.show', $asset))->assertForbidden();
    }

    public function test_documents_are_private_and_access_controlled(): void
    {
        Storage::fake('local');
        $hr = $this->makeUser('hr_manager');
        $owner = $this->makeUser('employee');
        $colleague = $this->makeUser('employee');

        $this->actingAs($hr)->post('/documents', [
            'title' => 'Signed Agreement Jx', 'category' => 'contract', 'employee_id' => $owner->employee->id,
            'employee_visible' => '1', 'file' => UploadedFile::fake()->create('contract.pdf', 120, 'application/pdf'),
        ])->assertRedirect('/documents');
        $this->actingAs($hr)->post('/documents', [
            'title' => 'Background check', 'category' => 'employee_document', 'employee_id' => $owner->employee->id,
            'employee_visible' => '0', 'file' => UploadedFile::fake()->create('check.pdf', 20, 'application/pdf'),
        ]);
        $this->actingAs($hr)->post('/documents', [
            'title' => 'Handbook', 'category' => 'policy', 'employee_visible' => '1', 'file' => UploadedFile::fake()->create('handbook.pdf', 20, 'application/pdf'),
        ]);

        [$contract, $hidden, $policy] = Document::orderBy('id')->get()->all();
        $this->assertStringStartsWith('documents/', $contract->file_path);
        $this->assertStringNotContainsString('contract.pdf', $contract->file_path); // randomised name
        Storage::disk('local')->assertExists($contract->file_path);

        $this->actingAs($owner)->get(route('documents.download', $contract))->assertOk();
        $this->actingAs($owner)->get(route('documents.download', $policy))->assertOk();
        $this->actingAs($owner)->get(route('documents.download', $hidden))->assertForbidden();
        $this->actingAs($colleague)->get(route('documents.download', $contract))->assertForbidden();
        $this->actingAs($colleague)->get('/documents')->assertOk()->assertSee('Handbook')->assertDontSee('Signed Agreement Jx');
        $this->actingAs($owner)->delete(route('documents.destroy', $contract))->assertForbidden();

        $this->actingAs($hr)->post('/documents', ['title' => 'Bad', 'category' => 'contract', 'file' => UploadedFile::fake()->create('x.php', 1)])->assertSessionHasErrors('file');

        $this->actingAs($hr)->delete(route('documents.destroy', $contract))->assertSessionHas('success');
        Storage::disk('local')->assertMissing($contract->file_path);
    }

    public function test_document_expiry_alerts(): void
    {
        Notification::fake();
        Storage::fake('local');
        $hr = $this->makeUser('hr_manager');
        $owner = $this->makeUser('employee');
        Document::create(['title' => 'Passport', 'category' => 'id_document', 'file_path' => 'documents/x.pdf', 'original_name' => 'x.pdf', 'employee_id' => $owner->employee->id, 'expiry_date' => now()->addDays(10), 'employee_visible' => true]);
        Document::create(['title' => 'Far away', 'category' => 'id_document', 'file_path' => 'documents/y.pdf', 'original_name' => 'y.pdf', 'expiry_date' => now()->addYear(), 'employee_visible' => true]);

        $this->actingAs($hr)->get('/documents?expiry=expiring')->assertOk()->assertSee('Passport')->assertDontSee('Far away');
        $this->artisan('officepro:document-expiry')->assertSuccessful();
        Notification::assertSentTo([$hr, $owner], DocumentExpiring::class);
        Notification::assertSentToTimes($hr, DocumentExpiring::class, 1);
    }
}
