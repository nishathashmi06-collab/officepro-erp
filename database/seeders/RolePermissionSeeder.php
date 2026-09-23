<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permissions::all() as $slug => $meta) {
            Permission::updateOrCreate(['slug' => $slug], ['name' => $meta['name'], 'module' => $meta['module']]);
        }

        $ids = Permission::pluck('id', 'slug');

        foreach (Permissions::roles() as $slug => $meta) {
            $role = Role::updateOrCreate(['slug' => $slug], ['name' => $meta['name'], 'description' => $meta['description'], 'is_system' => true]);

            // Only set defaults for new roles so customised permissions survive re-seeding.
            if ($role->wasRecentlyCreated || $slug === Permissions::ROLE_SUPER_ADMIN) {
                $role->permissions()->sync($ids->only($meta['permissions'])->values());
            }
        }
    }
}
