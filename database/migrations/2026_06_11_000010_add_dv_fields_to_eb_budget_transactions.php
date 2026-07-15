<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_budget_transactions', function (Blueprint $table) {
            $table->enum('status', ['draft', 'approved', 'paid', 'cancelled'])
                ->default('draft')->after('type');

            $table->enum('fund_cluster', ['general_fund', 'trust_fund', 'drrmf', 'sef', 'sk_fund'])
                ->default('general_fund')->after('status');

            $table->enum('mode_of_payment', ['cash', 'check', 'bank_transfer'])
                ->nullable()->after('payee');

            $table->string('check_no', 100)->nullable()->after('mode_of_payment');
            $table->date('check_date')->nullable()->after('check_no');

            $table->string('approved_by', 255)->nullable()->after('check_date');
        });
    }

    public function down(): void
    {
        Schema::table('eb_budget_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'fund_cluster', 'mode_of_payment',
                'check_no', 'check_date', 'approved_by',
            ]);
        });
    }
};
