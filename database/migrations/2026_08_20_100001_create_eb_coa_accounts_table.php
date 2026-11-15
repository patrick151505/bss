<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('eb_coa_accounts', function (Blueprint $table) {
            // COA Revised Chart of Accounts for LGUs (COA Circular 2015-009).
            // Dashed format, e.g. 5-02-04-020 = Electricity Expenses.
            $table->string('code', 20)->primary();
            $table->string('title', 255);
            $table->enum('account_type', ['Asset', 'Liability', 'Equity', 'Income', 'Expense']);
            $table->string('account_group', 120)->nullable();
            // Derived from the code prefix so the budget forms can group rows:
            // 5-01 = PS, 5-02 = MOOE, 5-03 = FE, 1-xx = CO. Null for accounts
            // that are never budgeted against (income, liabilities).
            $table->enum('object_class', ['PS', 'MOOE', 'FE', 'CO'])->nullable();
            $table->boolean('is_budgetable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('account_type');
            $table->index('object_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_coa_accounts');
    }
};
