<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Bring eb_budget_line_items in line with BBPF02 (Programmed
     * Appropriation by PPA), the form the treasurer actually fills in.
     */
    public function up(): void
    {
        Schema::table('eb_budget_line_items', function (Blueprint $table) {
            // Required column on the printed form; links BBPF02 to the AIP.
            // Series per object class: PS 1000-xx, MOOE 3000-xx, CO 8000-xx,
            // FE 9000-xx.
            $table->string('aip_code', 20)->nullable()->after('object_code');
            $table->text('expected_result')->nullable()->after('appropriation');
            $table->text('performance_indicator')->nullable()->after('expected_result');
            $table->index('aip_code');
        });

        // An account listed on the form but not funded this year must be
        // distinguishable from one deliberately budgeted at zero — the form
        // keeps both, and only a real amount counts toward the totals.
        DB::statement('ALTER TABLE eb_budget_line_items MODIFY appropriation DECIMAL(15,2) NULL DEFAULT NULL');

        // Existing rows were created under a NOT NULL DEFAULT 0 column, so a
        // stored 0.00 is ambiguous. Leave them as 0.00 rather than guessing;
        // only rows seeded from a template going forward start as NULL.
    }

    public function down(): void
    {
        DB::table('eb_budget_line_items')->whereNull('appropriation')->update(['appropriation' => 0.00]);
        DB::statement('ALTER TABLE eb_budget_line_items MODIFY appropriation DECIMAL(15,2) NOT NULL DEFAULT 0.00');

        Schema::table('eb_budget_line_items', function (Blueprint $table) {
            $table->dropIndex(['aip_code']);
            $table->dropColumn(['aip_code', 'expected_result', 'performance_indicator']);
        });
    }
};
