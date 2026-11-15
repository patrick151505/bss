<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissions for Ordinances & Resolutions.
 *   approve = advance the status (readings → approved → effective), veto, repeal, add timeline notes.
 * Super Admin gets all, Staff can record and edit, Viewer can read.
 */
return new class extends Migration {
    const PERMS = ['legislation.view', 'legislation.create', 'legislation.edit', 'legislation.delete', 'legislation.approve'];
    const ROLES = [
        'Super Admin' => self::PERMS,
        'Staff'       => ['legislation.view', 'legislation.create', 'legislation.edit'],
        'Viewer'      => ['legislation.view'],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (self::PERMS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        foreach (self::ROLES as $role => $perms) {
            Role::where('name', $role)->where('guard_name', 'web')->first()?->givePermissionTo($perms);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::whereIn('name', self::PERMS)->get()->each->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
