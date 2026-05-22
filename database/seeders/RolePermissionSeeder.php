<?php

namespace Database\Seeders;

use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (PermissionCatalog::all() as $permission => $label) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach (PermissionCatalog::defaultsByRole() as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($permissions);
        }
    }
}
