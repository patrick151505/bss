<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Split the single "inventory.*" permission set into one set per area so a role
 * can, for example, book the ambulance without touching stock:
 *
 *   inventory_items       view, create, edit, delete, stock (stock-in / adjust)
 *   inventory_categories  view, create, edit, delete
 *   inventory_releases    view, create, approve (approve / reject / mark released)
 *   inventory_rentals     view, create, edit (release / return / payment / cancel)
 *   inventory_schedule    view, create, edit (edit / complete / cancel)
 *
 * Every role and user keeps exactly the access it had: each old permission is
 * replaced by the new permissions it used to cover. The old ones are then removed.
 */
return new class extends Migration {
    const MAP = [
        'inventory.view'   => ['inventory_items.view', 'inventory_categories.view', 'inventory_releases.view', 'inventory_rentals.view', 'inventory_schedule.view'],
        'inventory.create' => ['inventory_items.create', 'inventory_categories.create', 'inventory_releases.create', 'inventory_rentals.create', 'inventory_schedule.create'],
        'inventory.edit'   => ['inventory_items.edit', 'inventory_items.stock', 'inventory_categories.edit', 'inventory_releases.approve', 'inventory_rentals.edit', 'inventory_schedule.edit'],
        'inventory.delete' => ['inventory_items.delete', 'inventory_categories.delete'],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $new = collect(self::MAP)->flatten()->unique();
        foreach ($new as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $ids = Permission::whereIn('name', $new->merge(array_keys(self::MAP)))->pluck('id', 'name');

        DB::transaction(function () use ($ids) {
            foreach (self::MAP as $old => $replacements) {
                if (!isset($ids[$old])) continue;

                // Roles
                foreach (DB::table('role_has_permissions')->where('permission_id', $ids[$old])->pluck('role_id') as $roleId) {
                    foreach ($replacements as $p) {
                        DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $ids[$p], 'role_id' => $roleId]);
                    }
                }
                // Permissions given directly to a user
                foreach (DB::table('model_has_permissions')->where('permission_id', $ids[$old])->get() as $row) {
                    foreach ($replacements as $p) {
                        DB::table('model_has_permissions')->insertOrIgnore([
                            'permission_id' => $ids[$p], 'model_type' => $row->model_type, 'model_id' => $row->model_id,
                        ]);
                    }
                }
            }
            Permission::whereIn('name', array_keys(self::MAP))->get()->each->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(self::MAP) as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $ids = Permission::pluck('id', 'name');

        DB::transaction(function () use ($ids) {
            // A role gets the old permission back if it held any of the new ones it covered.
            foreach (self::MAP as $old => $replacements) {
                $newIds = collect($replacements)->map(fn($p) => $ids[$p] ?? null)->filter();
                foreach (DB::table('role_has_permissions')->whereIn('permission_id', $newIds)->distinct()->pluck('role_id') as $roleId) {
                    DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $ids[$old], 'role_id' => $roleId]);
                }
                foreach (DB::table('model_has_permissions')->whereIn('permission_id', $newIds)->get()->unique(fn($r) => $r->model_type . $r->model_id) as $row) {
                    DB::table('model_has_permissions')->insertOrIgnore(['permission_id' => $ids[$old], 'model_type' => $row->model_type, 'model_id' => $row->model_id]);
                }
            }
            Permission::where('name', 'like', 'inventory\_%.%')->get()->each->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
