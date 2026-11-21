<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * kiosk.use — open the self-service kiosk screen (it also needs a paired device).
 * A "Kiosk" role with only that permission is created for the account the kiosk
 * tablet logs in with; accounts with that role can't open any other page.
 * Super Admin keeps holding every permission.
 */
return new class extends Migration {
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $perm = Permission::firstOrCreate(['name' => 'kiosk.use', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Kiosk', 'guard_name' => 'web'])->givePermissionTo($perm);
        Role::where('name', 'Super Admin')->first()?->givePermissionTo($perm);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::where('name', 'Kiosk')->whereDoesntHave('users')->delete();
        Permission::where('name', 'kiosk.use')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
