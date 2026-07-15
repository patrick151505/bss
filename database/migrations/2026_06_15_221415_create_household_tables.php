<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Relations lookup
        Schema::create('eb_relation', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('description', 100);
        });

        // Physical household address
        Schema::create('eb_household', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('addressId');
            $table->integer('blk')->nullable();
            $table->integer('lot')->nullable();
            $table->string('phaseStreet', 100)->nullable();
            $table->string('no', 100)->nullable();
            $table->string('internal', 99)->nullable();
            $table->text('completeAdress')->nullable();
            $table->integer('citizenHeadId')->nullable();
            $table->integer('user_id');
            $table->datetime('date_created');
            $table->datetime('date_lastupdated');
        });

        // Family unit inside a household
        Schema::create('eb_family', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('householdId');
            $table->integer('citizenId');
            $table->datetime('date_created');
            $table->datetime('date_last_updated');
        });

        // Add family columns to eb_citizen
        Schema::table('eb_citizen', function (Blueprint $table) {
            $table->integer('familyId')->nullable()->after('profile');
            $table->tinyInteger('isHead')->default(0)->after('familyId');
            $table->integer('relationId')->nullable()->after('isHead');
        });
    }

    public function down(): void
    {
        Schema::table('eb_citizen', function (Blueprint $table) {
            $table->dropColumn(['familyId', 'isHead', 'relationId']);
        });
        Schema::dropIfExists('eb_family');
        Schema::dropIfExists('eb_household');
        Schema::dropIfExists('eb_relation');
    }
};
