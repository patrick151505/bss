<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Pets of citizens (Citizen → Pets): each pet belongs to one citizen, has a
 * category (Dog, Cat, …; staff can type new ones), a photo and details such
 * as breed, sex, color, birth date and the last anti-rabies shot.
 *
 * Permissions pets.view/create/edit/delete go to the Super Admin and to every
 * role holding the matching citizens.* permission.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_pet_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        foreach (['Dog', 'Cat', 'Bird', 'Chicken / Rooster', 'Rabbit', 'Fish', 'Other'] as $i => $name) {
            DB::table('eb_pet_categories')->insert([
                'name' => $name, 'is_active' => 1, 'sort_order' => $i + 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        Schema::create('eb_pets', function (Blueprint $table) {
            $table->id();
            $table->integer('citizen_id');                         // eb_citizen.id is a signed int(11)
            $table->foreignId('category_id')->nullable()->constrained('eb_pet_categories')->nullOnDelete();
            $table->string('name', 100);
            $table->string('breed', 100)->nullable();
            $table->string('sex', 10)->nullable();                // male | female
            $table->string('color', 100)->nullable();             // color / markings
            $table->date('birth_date')->nullable();
            $table->boolean('is_neutered')->default(false);       // spayed / neutered
            $table->date('last_vaccinated_on')->nullable();       // last anti-rabies shot
            $table->string('photo')->nullable();
            $table->string('status', 20)->default('active');      // active | lost | deceased
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('citizen_id')->references('id')->on('eb_citizen')->cascadeOnDelete();
            $table->index(['status', 'category_id']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        // pets.X follows the citizens permission a role already has (delete ← archive).
        $from = ['view' => 'citizens.view', 'create' => 'citizens.create', 'edit' => 'citizens.edit', 'delete' => 'citizens.archive'];
        foreach ($from as $action => $source) {
            $perm = Permission::firstOrCreate(['name' => "pets.{$action}", 'guard_name' => 'web']);
            Role::where('name', 'Super Admin')->first()?->givePermissionTo($perm);
            if (Permission::where('name', $source)->where('guard_name', 'web')->exists()) {
                Role::permission($source)->get()->each(fn ($r) => $r->givePermissionTo($perm));
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_pets');
        Schema::dropIfExists('eb_pet_categories');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::where('name', 'like', 'pets.%')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
