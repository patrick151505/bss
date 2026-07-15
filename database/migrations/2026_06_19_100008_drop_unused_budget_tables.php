<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        // Tables from old legislative workflow — not needed in the simplified spec
        Schema::dropIfExists('eb_ordinance_reviews');
        Schema::dropIfExists('eb_ordinance_votes');
        Schema::dropIfExists('eb_appropriations_ordinances');
        Schema::dropIfExists('eb_calamity_declarations');
        Schema::dropIfExists('eb_obligations');
        Schema::dropIfExists('eb_cafs');
        Schema::dropIfExists('eb_compliance_deadlines');
        Schema::dropIfExists('eb_fiscal_year_phase_log');
        Schema::dropIfExists('eb_budget_messages');
        Schema::dropIfExists('eb_budget_income_estimates');
        Schema::dropIfExists('eb_budget_item_collections');
        Schema::dropIfExists('eb_budget_items');
        Schema::dropIfExists('eb_budget_fund_rules');
        Schema::dropIfExists('eb_budget_categories');
        Schema::dropIfExists('eb_fund_clusters');
        Schema::dropIfExists('eb_units');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Not restoring — these tables belong to a removed module
    }
};
