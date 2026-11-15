<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * BBPF02 legitimately repeats a line item name within one program and
     * object class — General Administration lists "Other Machinery and
     * Equipment" twice under CO, and SC/PWD lists the same Handog activity
     * for Pasko and Kaarawan. The old unique index rejected the treasurer's
     * actual form, so uniqueness moves to the AIP reference code, which the
     * form does guarantee is unique.
     */
    public function up(): void
    {
        // MySQL uses the old index to back the fiscal_year_id foreign key and
        // refuses to drop it while it is the only candidate, so create the
        // replacement first.
        Schema::table('eb_budget_line_items', function (Blueprint $table) {
            $table->unique(['fiscal_year_id', 'aip_code'], 'line_items_fy_aip_unique');
        });

        Schema::table('eb_budget_line_items', function (Blueprint $table) {
            $table->dropUnique('line_items_fy_prog_cls_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('eb_budget_line_items', function (Blueprint $table) {
            $table->unique(
                ['fiscal_year_id', 'program_id', 'object_class', 'name'],
                'line_items_fy_prog_cls_name_unique'
            );
        });

        Schema::table('eb_budget_line_items', function (Blueprint $table) {
            $table->dropUnique('line_items_fy_aip_unique');
        });
    }
};
