<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('eb_budget_settings', function (Blueprint $table) {
            $table->string('voucher_prefix_dv',      20)->default('DV')      ->after('ca_deadline_days_travel');
            $table->string('voucher_prefix_pcv',     20)->default('PCV')     ->after('voucher_prefix_dv');
            $table->string('voucher_prefix_payroll',  20)->default('PAY')     ->after('voucher_prefix_pcv');
            $table->tinyInteger('voucher_seq_padding')->unsigned()->default(3)->after('voucher_prefix_payroll');
        });
    }

    public function down(): void
    {
        Schema::table('eb_budget_settings', function (Blueprint $table) {
            $table->dropColumn(['voucher_prefix_dv','voucher_prefix_pcv','voucher_prefix_payroll','voucher_seq_padding']);
        });
    }
};
