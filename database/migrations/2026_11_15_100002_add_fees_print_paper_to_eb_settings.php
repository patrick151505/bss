<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settings → Print Paper: paper template (and optional pinned version) for
 * the Document Fee Summary printout.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('eb_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('fees_paper_template_id')->nullable()->after('inventory_paper_version_id');
            $table->unsignedBigInteger('fees_paper_version_id')->nullable()->after('fees_paper_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('eb_settings', function (Blueprint $table) {
            $table->dropColumn(['fees_paper_template_id', 'fees_paper_version_id']);
        });
    }
};
