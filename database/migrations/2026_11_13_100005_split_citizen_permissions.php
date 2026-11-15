<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Finer citizen permissions. New:
 *   citizens.approve       set a record's approval status (pending / approved / rejected)
 *   citizens.tag           assign tags to a citizen
 *   citizens.demographics  open the Demographics dashboard
 *   citizen_ids.*          view, generate, print, signature, template (Barangay ID cards)
 *   birthdays.*            view, export
 * Removed: citizens.delete — nothing used it (removing a citizen is "archive").
 *
 * Every role and user keeps the access it had: the old permission that used to
 * cover an action grants its new permission.
 */
return new class extends Migration {
    const GRANTS = [
        'citizens.view'   => ['citizens.demographics', 'citizen_ids.view', 'citizen_ids.print', 'birthdays.view'],
        'citizens.create' => ['citizen_ids.generate', 'citizens.approve'],
        'citizens.edit'   => ['citizens.approve', 'citizens.tag', 'citizen_ids.signature', 'citizen_ids.template'],
        'citizens.export' => ['birthdays.export'],
    ];
    const REMOVED = ['citizens.delete'];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $new = collect(self::GRANTS)->flatten()->unique();
        foreach ($new as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $ids = Permission::pluck('id', 'name');

        DB::transaction(function () use ($ids) {
            foreach (self::GRANTS as $old => $grants) {
                if (!isset($ids[$old])) continue;
                foreach (DB::table('role_has_permissions')->where('permission_id', $ids[$old])->pluck('role_id') as $roleId) {
                    foreach ($grants as $p) {
                        DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $ids[$p], 'role_id' => $roleId]);
                    }
                }
                foreach (DB::table('model_has_permissions')->where('permission_id', $ids[$old])->get() as $row) {
                    foreach ($grants as $p) {
                        DB::table('model_has_permissions')->insertOrIgnore(['permission_id' => $ids[$p], 'model_type' => $row->model_type, 'model_id' => $row->model_id]);
                    }
                }
            }
            Permission::whereIn('name', self::REMOVED)->get()->each->delete();
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (self::REMOVED as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        Permission::whereIn('name', collect(self::GRANTS)->flatten()->unique())->get()->each->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
