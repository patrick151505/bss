<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Roles → Document Types (Issue):
 *  - adds "issue_document.all" (issue every type, including ones added later);
 *  - removes issue permissions left behind by deleted document types, which
 *    showed on the Roles page as bare numbers;
 *  - creates the permission for any type that is missing one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'issue_document.all', 'guard_name' => 'web']);
        Role::where('name', 'Super Admin')->first()?->givePermissionTo('issue_document.all');

        $typeIds = DB::table('eb_document_types')->pluck('id')->map(fn ($id) => (int) $id)->all();

        Permission::where('name', 'like', 'issue_document.%')->get()
            ->filter(fn ($p) => ctype_digit(substr($p->name, 15)) && ! in_array((int) substr($p->name, 15), $typeIds, true))
            ->each(fn ($p) => $p->delete());     // also detaches it from roles / users

        foreach ($typeIds as $id) {
            Permission::firstOrCreate(['name' => 'issue_document.' . $id, 'guard_name' => 'web']);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'issue_document.all')->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
