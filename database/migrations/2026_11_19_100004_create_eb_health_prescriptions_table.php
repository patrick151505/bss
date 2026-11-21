<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prescription (reseta) for a check-up: medicines the patient must buy
 * because the health station doesn't have them. Nothing comes out of stock.
 *   eb_health_prescriptions   one line per medicine: name/strength, quantity, how to take
 * The prescriber is the check-up's "Attended by"; licence and PTR numbers are
 * kept on the check-up so they print on the reseta.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('eb_health_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkup_id')->constrained('eb_health_checkups')->cascadeOnDelete();
            $table->string('medicine', 255);                 // generic name, strength and form, e.g. Amoxicillin 500 mg capsule
            $table->string('quantity', 50)->nullable();      // e.g. #21, 1 bottle
            $table->string('sig', 255)->nullable();          // how to take, e.g. 1 capsule 3x a day for 7 days
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('eb_health_checkups', function (Blueprint $table) {
            $table->string('prescriber_license', 50)->nullable()->after('attended_by');
            $table->string('prescriber_ptr', 50)->nullable()->after('prescriber_license');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_health_prescriptions');
        Schema::table('eb_health_checkups', function (Blueprint $table) {
            $table->dropColumn(['prescriber_license', 'prescriber_ptr']);
        });
    }
};
