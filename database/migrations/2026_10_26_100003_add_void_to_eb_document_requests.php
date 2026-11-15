<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    /**
     * Voiding cancels a RELEASED document without deleting it: status becomes
     * "voided", the record and its control number are kept for audit, and it
     * drops out of fee totals. Before release, Reject covers the same need.
     */
    public function up(): void
    {
        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->text('void_reason')->nullable()->after('remarks');
            $table->dateTime('voided_at')->nullable()->after('void_reason');
            $table->unsignedBigInteger('voided_by')->nullable()->after('voided_at');
        });

        // Created here so existing installs get it without re-running the
        // seeder. Super Admin holds explicit grants (no gate bypass).
        $permission = Permission::firstOrCreate(['name' => 'documents.void', 'guard_name' => 'web']);
        Role::where('name', 'Super Admin')->first()?->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->dropColumn(['void_reason', 'voided_at', 'voided_by']);
        });
        Permission::where('name', 'documents.void')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
