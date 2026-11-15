<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Pin a specific paper template VERSION for the blotter / inventory prints
     * (same idea as Document Types). NULL = always the template's latest
     * (current) version.
     */
    public function up(): void
    {
        Schema::table('eb_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('blotter_paper_version_id')->nullable()->after('blotter_paper_template_id');
            $table->unsignedBigInteger('inventory_paper_version_id')->nullable()->after('inventory_paper_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('eb_settings', function (Blueprint $table) {
            $table->dropColumn(['blotter_paper_version_id', 'inventory_paper_version_id']);
        });
    }
};
