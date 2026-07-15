<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('eb_fiscal_years', function (Blueprint $table) {
            // Add beginning cash balance
            if (!Schema::hasColumn('eb_fiscal_years', 'beginning_cash_balance')) {
                $table->decimal('beginning_cash_balance', 15, 2)->default(0)->after('is_active');
            }
            // Drop phase columns if they exist
            $cols = ['phase', 'phase_updated_at', 'phase_updated_by'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('eb_fiscal_years', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('eb_fiscal_years', function (Blueprint $table) {
            $table->dropColumn('beginning_cash_balance');
        });
    }
};
