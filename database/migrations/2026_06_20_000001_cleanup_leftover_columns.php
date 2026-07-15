<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // 1. Drop the orphaned items table (no model, no routes)
        Schema::dropIfExists('eb_budget_transaction_items');

        // 2. category_id already dropped in earlier migration — nothing to do here

        // 3. Widen fund_cluster on transactions from enum → varchar (enum was tied to old eb_fund_clusters)
        DB::statement("ALTER TABLE eb_budget_transactions MODIFY COLUMN fund_cluster VARCHAR(50) NULL");

        // 4. Clear out the old allocation rows that have no line_name (junk from old category system)
        //    These rows have object_class set but empty line_name — they're unusable
        DB::table('eb_budget_allocations')->where('line_name', '')->delete();
        DB::table('eb_budget_allocations')->whereNull('line_name')->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Not reversible — old junk data is gone intentionally
    }
};
