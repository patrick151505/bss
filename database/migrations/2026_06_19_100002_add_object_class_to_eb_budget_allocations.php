<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('eb_budget_allocations', function (Blueprint $table) {
            // Object class: PS / MOOE / CO / Special
            if (!Schema::hasColumn('eb_budget_allocations', 'object_class')) {
                $table->enum('object_class', ['PS', 'MOOE', 'CO', 'special'])->after('fiscal_year_id');
            }
            // Fund type for special allocations
            if (!Schema::hasColumn('eb_budget_allocations', 'fund_type')) {
                $table->enum('fund_type', ['general', 'dev_fund', 'sk_fund', 'calamity', 'gad'])
                      ->default('general')->after('object_class');
            }
            // Line name (e.g. "Salaries and Wages", "Office Supplies")
            if (!Schema::hasColumn('eb_budget_allocations', 'line_name')) {
                $table->string('line_name')->after('fund_type');
            }
            // Original appropriation from enacted budget
            if (!Schema::hasColumn('eb_budget_allocations', 'appropriation')) {
                $table->decimal('appropriation', 15, 2)->default(0)->after('line_name');
            }
            // Drop old category_id FK — replaced by object_class + line_name
            if (Schema::hasColumn('eb_budget_allocations', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }
            // Rename allocated_amount → keep as is for computed total
            // Drop old unique constraint if it exists
            try {
                $table->dropUnique(['fiscal_year_id', 'category_id']);
            } catch (\Exception $e) { /* already gone */ }
        });
    }

    public function down(): void
    {
        Schema::table('eb_budget_allocations', function (Blueprint $table) {
            $table->dropColumn(['object_class', 'fund_type', 'line_name', 'appropriation']);
        });
    }
};
