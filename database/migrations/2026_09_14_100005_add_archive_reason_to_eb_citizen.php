<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Why a citizen was archived, who did it and when. Cleared on restore,
     * so these only ever describe the current archived state.
     */
    public function up(): void
    {
        Schema::table('eb_citizen', function (Blueprint $table) {
            $table->text('archive_reason')->nullable()->after('is_active');
            $table->dateTime('archived_at')->nullable()->after('archive_reason');
            $table->unsignedBigInteger('archived_by')->nullable()->after('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('eb_citizen', function (Blueprint $table) {
            $table->dropColumn(['archive_reason', 'archived_at', 'archived_by']);
        });
    }
};
