<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('eb_budget_settings', function (Blueprint $table) {
            // Mandatory fund rates (percent) and bases
            $table->decimal('dev_fund_rate', 5, 2)->default(20.00)->after('fund_account_no');
            $table->string('dev_fund_base', 30)->default('nta')->after('dev_fund_rate');

            $table->decimal('sk_fund_rate', 5, 2)->default(10.00)->after('dev_fund_base');
            $table->string('sk_fund_base', 30)->default('general_fund')->after('sk_fund_rate');

            $table->decimal('calamity_rate', 5, 2)->default(5.00)->after('sk_fund_base');
            $table->string('calamity_base', 30)->default('regular_sources')->after('calamity_rate');

            $table->decimal('gad_rate', 5, 2)->default(5.00)->after('calamity_base');
            $table->string('gad_base', 30)->default('total_budget')->after('gad_rate');
        });

        // Update the existing settings row with defaults
        DB::table('eb_budget_settings')->where('id', 1)->update([
            'dev_fund_rate'   => 20.00,
            'dev_fund_base'   => 'nta',
            'sk_fund_rate'    => 10.00,
            'sk_fund_base'    => 'general_fund',
            'calamity_rate'   => 5.00,
            'calamity_base'   => 'regular_sources',
            'gad_rate'        => 5.00,
            'gad_base'        => 'total_budget',
        ]);
    }

    public function down(): void
    {
        Schema::table('eb_budget_settings', function (Blueprint $table) {
            $table->dropColumn([
                'dev_fund_rate', 'dev_fund_base',
                'sk_fund_rate', 'sk_fund_base',
                'calamity_rate', 'calamity_base',
                'gad_rate', 'gad_base',
            ]);
        });
    }
};
