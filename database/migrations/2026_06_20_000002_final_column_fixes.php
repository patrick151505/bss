<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Make voucher_type nullable so old records don't break the UI
        // (existing 5 records have NULL; we default them to DV)
        DB::statement("ALTER TABLE eb_budget_transactions MODIFY COLUMN voucher_type ENUM('DV','PCV','Payroll') NULL");
        DB::table('eb_budget_transactions')->whereNull('voucher_type')->update(['voucher_type' => 'DV']);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void {}
};
