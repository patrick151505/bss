<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    /**
     * A hold stops a citizen from being issued documents until it is lifted.
     * Holds are never deleted: lifting one stamps lifted_at/lifted_by, so the
     * table doubles as the citizen's hold history. A citizen is "on hold"
     * while they have a row with lifted_at = NULL.
     */
    public function up(): void
    {
        Schema::create('eb_citizen_holds', function (Blueprint $table) {
            $table->id();
            $table->integer('citizen_id');                 // eb_citizen.id is int(11)
            $table->text('reason');
            $table->unsignedBigInteger('placed_by')->nullable();
            $table->dateTime('placed_at');
            $table->unsignedBigInteger('lifted_by')->nullable();
            $table->dateTime('lifted_at')->nullable();
            $table->text('lift_note')->nullable();
            $table->timestamps();

            $table->index(['citizen_id', 'lifted_at']);
            $table->foreign('citizen_id')->references('id')->on('eb_citizen')->cascadeOnDelete();
        });

        // Created here (not only in PermissionSeeder) so existing installs get
        // it without re-running the seeder, which would reset role grants.
        // Super Admin holds explicit grants (there is no gate bypass), so it
        // is given the new permission too; other roles are granted via Roles.
        $permission = Permission::firstOrCreate(['name' => 'citizens.hold', 'guard_name' => 'web']);
        Role::where('name', 'Super Admin')->first()?->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_citizen_holds');
        Permission::where('name', 'citizens.hold')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
