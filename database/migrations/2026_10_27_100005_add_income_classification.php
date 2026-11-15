<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    /**
     * Optional income classification (off by default).
     * - Families may record a monthly income, or just pick a class when the exact
     *   amount is unknown. Nothing is required.
     * - Settings hold the on/off switch and the per-person poverty threshold the
     *   classes are measured against.
     * - Seeing or editing income needs its own permission.
     */
    public function up(): void
    {
        Schema::table('eb_family', function (Blueprint $table) {
            $table->decimal('monthly_income', 12, 2)->nullable()->after('citizenId');
            $table->string('income_class', 20)->nullable()->after('monthly_income');   // picked when the amount is unknown
            $table->dateTime('income_updated_at')->nullable()->after('income_class');
            $table->unsignedBigInteger('income_updated_by')->nullable()->after('income_updated_at');
        });

        Schema::table('eb_settings', function (Blueprint $table) {
            $table->boolean('income_enabled')->default(false);
            $table->decimal('poverty_threshold', 10, 2)->nullable();   // per person, per month (PSA)
        });

        Permission::firstOrCreate(['name' => 'households.income', 'guard_name' => 'web']);
        Role::where('name', 'Super Admin')->first()?->givePermissionTo('households.income');
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('eb_family', function (Blueprint $table) {
            $table->dropColumn(['monthly_income', 'income_class', 'income_updated_at', 'income_updated_by']);
        });
        Schema::table('eb_settings', function (Blueprint $table) {
            $table->dropColumn(['income_enabled', 'poverty_threshold']);
        });
        Permission::where('name', 'households.income')->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
