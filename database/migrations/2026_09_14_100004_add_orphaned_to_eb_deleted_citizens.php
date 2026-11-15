<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Records what still referenced the citizen at the moment of deletion —
     * blotter cases, document requests and so on — so the audit trail shows
     * which links were left dangling.
     */
    public function up(): void
    {
        Schema::table('eb_deleted_citizens', function (Blueprint $table) {
            $table->json('orphaned')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('eb_deleted_citizens', function (Blueprint $table) {
            $table->dropColumn('orphaned');
        });
    }
};
