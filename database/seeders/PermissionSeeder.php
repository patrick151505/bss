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
            // archive = set a citizen inactive; restore = return them to the
            // active list. Separate from edit so records can be corrected
            // without granting the ability to remove them from circulation.
            // hold = place/lift a hold that blocks document issuance.
            // approve = set approval status; tag = assign tags; demographics = dashboard.
            'citizens'      => ['view', 'create', 'edit', 'approve', 'tag', 'export', 'archive', 'restore', 'hold', 'demographics'],
            // Barangay ID cards: generate = issue a card; signature = add/remove
            // the signature; template = the ID Template designer.
            'citizen_ids'   => ['view', 'generate', 'print', 'signature', 'template'],
            'birthdays'     => ['view', 'export'],
            // income = see and edit family income (only used when Income
            // Classification is switched on in Settings).
            'households'    => ['view', 'create', 'edit', 'delete', 'income'],
            'blotter'       => ['view', 'create', 'edit', 'delete'],
            // Ordinances & Resolutions. approve = advance the status (readings,
            // approval, effectivity, veto) and add timeline entries.
            'legislation'   => ['view', 'create', 'edit', 'delete', 'approve'],
            // Health check-ups. dispense = give medicines from Inventory stock.
            'health'        => ['view', 'create', 'edit', 'delete', 'dispense'],
            // void = cancel a released document while keeping it on record.
            'documents'     => ['view', 'create', 'edit', 'delete', 'void', 'fees'],
            // Inventory is split per area. stock = stock-in and adjustments;
            // approve = approve, reject and mark releases as released.
            'inventory_items'      => ['view', 'create', 'edit', 'delete', 'stock'],
            'inventory_categories' => ['view', 'create', 'edit', 'delete'],
            'inventory_releases'   => ['view', 'create', 'approve'],
            'inventory_rentals'    => ['view', 'create', 'edit'],
            'inventory_schedule'   => ['view', 'create', 'edit'],
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

        // Baseline non-admin roles. Beyond being useful defaults, these make the
        // permission system testable: with only a Super Admin account every gate
        // is bypassed, so a missing check looks identical to a working one.
        $viewAll = collect($modules)
            ->filter(fn ($actions) => in_array('view', $actions, true))
            ->keys()
            ->map(fn ($module) => "{$module}.view")
            ->all();

        // Read-only across every module — the role to test "no edit" against.
        // Health records are sensitive: the read-only role doesn't see them.
        Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web'])
            ->syncPermissions(array_values(array_diff($viewAll, ['health.view'])));

        // Day-to-day desk work: full citizen/household/document handling, plus
        // read access elsewhere. No user, role, settings or budget writes.
        Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web'])
            ->syncPermissions(array_merge($viewAll, [
                'citizens.create', 'citizens.edit', 'citizens.export',
                'households.create', 'households.edit',
                'documents.create', 'documents.edit', 'documents.fees',
                'blotter.create', 'blotter.edit',
                'legislation.create', 'legislation.edit',
                'health.create', 'health.edit',
                'events.create', 'events.edit',
                'tags.create', 'tags.edit',
            ]));
    }
}
