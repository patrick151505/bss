<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('eb_settings', function (Blueprint $table) {
            $table->string('citizen_id_prefix', 20)->default('EBT')->after('captain_position');
            $table->tinyInteger('citizen_id_digits')->default(6)->after('citizen_id_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('eb_settings', function (Blueprint $table) {
            $table->dropColumn(['citizen_id_prefix', 'citizen_id_digits']);
        });
    }
};
