<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settings → Print Paper: paper template (and optional pinned version) for
 * the Health Check-up Record printout.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('eb_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('health_paper_template_id')->nullable()->after('fees_paper_version_id');
            $table->unsignedBigInteger('health_paper_version_id')->nullable()->after('health_paper_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('eb_settings', function (Blueprint $table) {
            $table->dropColumn(['health_paper_template_id', 'health_paper_version_id']);
        });
    }
};
