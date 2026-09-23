<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reference data required in every installation.
        $this->call([
            RolePermissionSeeder::class,
            ReferenceDataSeeder::class,
        ]);

        // DEMO / SEED DATA — fictional people and records for evaluation.
        // Skip it in production with: php artisan db:seed --class=ProductionSeeder
        $this->call(DemoDataSeeder::class);
    }
}
