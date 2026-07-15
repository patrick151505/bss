<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_budget_settings', function (Blueprint $table) {
            $table->id();
            // Mandatory allocation percentages (RA 7160)
            $table->decimal('ps_cap_percent', 5, 2)->default(55.00);
            $table->decimal('dev_fund_percent', 5, 2)->default(20.00);
            $table->decimal('sk_fund_percent', 5, 2)->default(10.00);
            $table->decimal('calamity_fund_percent', 5, 2)->default(5.00);
            $table->decimal('gad_percent', 5, 2)->default(5.00);
            $table->decimal('senior_pwd_percent', 5, 2)->default(1.00);
            $table->decimal('calamity_qrf_max_percent', 5, 2)->default(30.00);
            // Computation bases
            $table->enum('ps_base', ['prior_year_actual', 'current_year_nta'])->default('prior_year_actual');
            $table->enum('dev_fund_base', ['nta', 'total_income'])->default('nta');
            // Submission deadline (default Oct 16)
            $table->tinyInteger('preparation_deadline_month')->default(10);
            $table->tinyInteger('preparation_deadline_day')->default(16);
            // Flags
            $table->boolean('sk_trust_carry_over_enabled')->default(true);
            $table->boolean('reenacted_budget_enabled')->default(true);
            $table->boolean('supplemental_ordinance_allowed')->default(true);
            $table->timestamps();
        });

        DB::table('eb_budget_settings')->insert([
            'id'         => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_budget_settings');
    }
};
