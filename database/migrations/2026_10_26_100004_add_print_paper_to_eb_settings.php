<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Default paper template (letterhead background + size + margins, from the
     * Documents → Paper Templates module) for the blotter report and the
     * inventory release slip. NULL = the plain built-in header.
     */
    public function up(): void
    {
        Schema::table('eb_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('blotter_paper_template_id')->nullable();
            $table->unsignedBigInteger('inventory_paper_template_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('eb_settings', function (Blueprint $table) {
            $table->dropColumn(['blotter_paper_template_id', 'inventory_paper_template_id']);
        });
    }
};
