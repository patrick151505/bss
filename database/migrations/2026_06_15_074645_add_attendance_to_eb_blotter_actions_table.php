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
        Schema::table('eb_blotter_actions', function (Blueprint $table) {
            $table->string('complainant_attendance', 20)->default('present')->after('outcome');
            $table->string('respondent_attendance', 20)->default('present')->after('complainant_attendance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eb_blotter_actions', function (Blueprint $table) {
            $table->dropColumn(['complainant_attendance', 'respondent_attendance']);
        });
    }
};
