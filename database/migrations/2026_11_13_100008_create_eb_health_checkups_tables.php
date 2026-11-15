<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Health check-up records (libreng check-up / walk-in consultations).
 *   eb_health_checkups           one visit: patient, vitals, complaint, findings, advice
 *   eb_health_checkup_medicines  medicines given at the visit (taken from Inventory stock)
 *
 * Permissions: health.view / create / edit / delete, plus health.dispense (give
 * medicines from Inventory). Health records are sensitive, so the Viewer role
 * does not get health.view by default.
 */
return new class extends Migration {
    const PERMS = ['health.view', 'health.create', 'health.edit', 'health.delete', 'health.dispense'];
    const ROLES = [
        'Super Admin' => self::PERMS,
        'Staff'       => ['health.view', 'health.create', 'health.edit'],
    ];

    public function up(): void
    {
        Schema::create('eb_health_checkups', function (Blueprint $table) {
            $table->id();
            // eb_citizen.id is a signed int
            $table->integer('citizen_id')->nullable()->index();
            // Walk-ins without a citizen record
            $table->string('patient_name', 255)->nullable();
            $table->unsignedTinyInteger('patient_age')->nullable();
            $table->string('patient_sex', 1)->nullable();          // M | F

            $table->dateTime('checked_at');
            $table->string('attended_by', 255)->nullable();         // doctor / nurse / BHW

            $table->unsignedSmallInteger('bp_systolic')->nullable();
            $table->unsignedSmallInteger('bp_diastolic')->nullable();
            $table->unsignedSmallInteger('pulse')->nullable();
            $table->unsignedSmallInteger('resp_rate')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();       // °C
            $table->unsignedTinyInteger('o2_sat')->nullable();      // %
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('blood_sugar', 5, 1)->nullable();       // mg/dL
            $table->boolean('sugar_fasting')->default(false);

            $table->text('complaint')->nullable();
            $table->text('findings')->nullable();
            $table->text('advice')->nullable();
            $table->string('referred_to', 255)->nullable();
            $table->date('follow_up_on')->nullable();

            $table->unsignedBigInteger('release_id')->nullable();   // eb_inventory_releases (citizens only)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('checked_at');
            $table->index('follow_up_on');
        });

        Schema::create('eb_health_checkup_medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkup_id')->constrained('eb_health_checkups')->cascadeOnDelete();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('name', 255);                            // item name at the time it was given
            $table->string('unit', 50)->nullable();
            $table->unsignedInteger('quantity');
            $table->string('instructions', 255)->nullable();        // e.g. 1 tab every 6 hours
            $table->timestamps();
        });

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
        Schema::dropIfExists('eb_health_checkup_medicines');
        Schema::dropIfExists('eb_health_checkups');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::whereIn('name', self::PERMS)->get()->each->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
