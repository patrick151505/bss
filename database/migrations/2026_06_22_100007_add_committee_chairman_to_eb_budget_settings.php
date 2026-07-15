<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_budget_settings', function (Blueprint $table) {
            $table->string('appropriation_chairman', 255)->nullable()->after('treasurer_position');
            $table->string('appropriation_chairman_position', 255)->nullable()->after('appropriation_chairman');
        });
    }

    public function down(): void
    {
        Schema::table('eb_budget_settings', function (Blueprint $table) {
            $table->dropColumn(['appropriation_chairman', 'appropriation_chairman_position']);
        });
    }
};
