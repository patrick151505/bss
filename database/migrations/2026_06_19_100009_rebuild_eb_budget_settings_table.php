<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('eb_budget_settings');
        Schema::create('eb_budget_settings', function (Blueprint $table) {
            $table->id();
            $table->string('barangay_name')->nullable();
            $table->string('municipality')->nullable();
            $table->string('province')->nullable();
            $table->string('treasurer_name')->nullable();
            $table->string('treasurer_position')->nullable()->default('Barangay Treasurer');
            $table->string('punong_barangay')->nullable();
            $table->string('fund_account_no')->nullable();
            $table->decimal('petty_cash_fund_limit', 15, 2)->default(5000);
            // COA: cash advances must be liquidated within X days (2 months in-station, 3 months travel)
            $table->unsignedTinyInteger('ca_deadline_days_local')->default(60);
            $table->unsignedTinyInteger('ca_deadline_days_travel')->default(90);
            $table->timestamps();
        });

        // Seed the single-row defaults
        DB::table('eb_budget_settings')->insert([
            'id'                      => 1,
            'treasurer_position'      => 'Barangay Treasurer',
            'petty_cash_fund_limit'   => 5000.00,
            'ca_deadline_days_local'  => 60,
            'ca_deadline_days_travel' => 90,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_budget_settings');
    }
};
