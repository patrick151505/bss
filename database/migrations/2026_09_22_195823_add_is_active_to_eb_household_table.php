<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Households had no way to be removed. Following the convention used for
 * citizens, they are ARCHIVED (set inactive) rather than deleted, so the
 * historical record and its family/member links stay intact for audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_household', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('citizenHeadId');
            $table->timestamp('archived_at')->nullable()->after('is_active');
            $table->unsignedBigInteger('archived_by')->nullable()->after('archived_at');

            // The list filters on this constantly.
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('eb_household', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn(['is_active', 'archived_at', 'archived_by']);
        });
    }
};
