<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * users.reset — email a user a link to set a new password (Users → key button).
 * Super Admin keeps holding every permission.
 */
return new class extends Migration {
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $perm = Permission::firstOrCreate(['name' => 'users.reset', 'guard_name' => 'web']);
        Role::where('name', 'Super Admin')->first()?->givePermissionTo($perm);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::where('name', 'users.reset')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
