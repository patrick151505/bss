<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $modules = [
            'citizens'      => ['view', 'create', 'edit', 'delete', 'export'],
            'households'    => ['view', 'create', 'edit', 'delete'],
            'blotter'       => ['view', 'create', 'edit', 'delete'],
            'documents'     => ['view', 'create', 'edit', 'delete'],
            'inventory'     => ['view', 'create', 'edit', 'delete'],
            'events'        => ['view', 'create', 'edit', 'delete'],
            'budget'        => ['view', 'create', 'edit', 'delete'],
            'tags'          => ['view', 'create', 'edit', 'delete'],
            'addresses'     => ['view', 'create', 'edit'],
            'settings'      => ['view', 'edit'],
            'users'         => ['view', 'create', 'edit', 'delete'],
            'roles'         => ['view', 'create', 'edit', 'delete'],
            'activity_logs' => ['view'],
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$module}.{$action}", 'guard_name' => 'web']);
            }
        }

        // Super Admin — gets all permissions via gate bypass (HasRoles trait)
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());
    }
}
