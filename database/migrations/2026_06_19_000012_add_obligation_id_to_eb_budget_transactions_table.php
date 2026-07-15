<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_budget_transactions', function (Blueprint $table) {
            $table->foreignId('obligation_id')->nullable()->after('recorded_by')
                  ->constrained('eb_obligations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('eb_budget_transactions', function (Blueprint $table) {
            $table->dropForeign(['obligation_id']);
            $table->dropColumn('obligation_id');
        });
    }
};
