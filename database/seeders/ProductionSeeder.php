<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Minimal, non-demo installation: roles, reference data and one super admin
 * whose credentials come from the environment (never hard-coded).
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolePermissionSeeder::class, ReferenceDataSeeder::class]);

        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('Set ADMIN_EMAIL and ADMIN_PASSWORD in .env to create the first super admin.');

            return;
        }

        User::firstOrCreate(['email' => $email], [
            'name' => env('ADMIN_NAME', 'Super Admin'),
            'password' => $password,
            'role_id' => Role::where('slug', Permissions::ROLE_SUPER_ADMIN)->value('id'),
            'status' => 'active',
            'is_primary' => ! User::where('is_primary', true)->exists(),
            'remember_token' => Str::random(10),
        ]);
    }
}
