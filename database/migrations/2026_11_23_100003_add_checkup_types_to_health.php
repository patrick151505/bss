<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Health check-up types (General consultation, Prenatal, …). Works like
 * document purposes: staff pick a saved type or type a new one, which is
 * kept for next time. The check-up stores the type's name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_health_checkup_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $defaults = ['General consultation', 'Blood pressure check', 'Blood sugar check', 'Prenatal check-up',
                     'Child check-up', 'Immunization', 'Senior citizen check-up', 'Follow-up visit'];
        foreach ($defaults as $i => $name) {
            DB::table('eb_health_checkup_types')->insert([
                'name' => $name, 'is_active' => 1, 'sort_order' => $i + 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        Schema::table('eb_health_checkups', function (Blueprint $table) {
            $table->string('checkup_type', 100)->nullable()->after('checked_at');
            $table->index('checkup_type');
        });
    }

    public function down(): void
    {
        Schema::table('eb_health_checkups', function (Blueprint $table) {
            $table->dropIndex(['checkup_type']);
            $table->dropColumn('checkup_type');
        });
        Schema::dropIfExists('eb_health_checkup_types');
    }
};
