<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('eb_budget_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('eb_budget_transactions', 'voucher_type')) {
                $table->enum('voucher_type', ['DV', 'PCV', 'Payroll'])->nullable()->after('type');
            }
            if (!Schema::hasColumn('eb_budget_transactions', 'voucher_no')) {
                $table->string('voucher_no', 50)->nullable()->after('voucher_type');
            }
            if (!Schema::hasColumn('eb_budget_transactions', 'tax_withheld')) {
                $table->decimal('tax_withheld', 15, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('eb_budget_transactions', 'allocation_id')) {
                $table->foreignId('allocation_id')->nullable()
                    ->constrained('eb_budget_allocations')->nullOnDelete()
                    ->after('fiscal_year_id');
            }
            // Drop old obligation_id FK if it exists
            if (Schema::hasColumn('eb_budget_transactions', 'obligation_id')) {
                try { $table->dropForeign(['obligation_id']); } catch (\Exception $e) {}
                $table->dropColumn('obligation_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('eb_budget_transactions', function (Blueprint $table) {
            $table->dropColumn(['voucher_type', 'voucher_no', 'tax_withheld', 'allocation_id']);
        });
    }
};
