<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Seeds the full demo data set and smoke-tests every main screen per role.
 */
class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seedReferenceData = false;

    public function test_demo_seed_and_all_pages_render_for_each_role(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(15, \App\Models\Employee::count());
        $this->assertTrue(User::where('email', 'superadmin@officepro.test')->value('is_primary'));

        $this->assertGreaterThan(0, \App\Models\Attendance::count());
        $pages = [
            'superadmin@officepro.test' => ['/', '/employees', '/employees/'.\App\Models\Employee::min('id'), '/departments', '/departments/'.\App\Models\Department::min('id'), '/designations', '/attendance', '/leaves', '/leave-types', '/tasks', '/tasks/board', '/tasks/'.\App\Models\Task::min('id'), '/payroll', '/payroll/'.\App\Models\Payroll::min('id'), '/payroll/'.\App\Models\Payroll::where('status', '!=', 'paid')->value('id').'/edit', '/expenses', '/expenses/'.\App\Models\Expense::min('id'), '/expense-categories', '/assets', '/assets/'.\App\Models\Asset::min('id'), '/documents', '/reports', '/reports/payroll', '/notifications', '/activity-logs', '/users', '/users/'.\App\Models\User::min('id'), '/roles', '/settings', '/profile/edit', '/search?q=Dan'],
            'hr@officepro.test' => ['/', '/employees', '/attendance', '/leaves', '/payroll', '/documents', '/reports'],
            'manager@officepro.test' => ['/', '/employees', '/attendance', '/leaves', '/tasks', '/tasks/board', '/expenses'],
            'employee@officepro.test' => ['/', '/employees', '/attendance', '/leaves', '/tasks', '/payroll', '/documents', '/notifications'],
        ];

        foreach ($pages as $email => $urls) {
            $user = User::where('email', $email)->firstOrFail();
            foreach ($urls as $url) {
                $response = $this->actingAs($user)->get($url);
                $this->assertContains($response->status(), [200, 302], "{$email} {$url} returned {$response->status()}");
            }
        }
    }
}
