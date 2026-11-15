<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * documents.fees — see the money side of Document Requests: the Fees
 * collected tile, the Fee Summary (possible income by document type) and
 * its printout. Super Admin and Staff get it; Viewer does not.
 */
return new class extends Migration {
    const PERM  = 'documents.fees';
    const ROLES = ['Super Admin', 'Staff'];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => self::PERM, 'guard_name' => 'web']);
        foreach (self::ROLES as $role) {
            Role::where('name', $role)->where('guard_name', 'web')->first()?->givePermissionTo(self::PERM);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::where('name', self::PERM)->get()->each->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
