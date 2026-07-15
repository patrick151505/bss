<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_budget_items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['unit', 'unit_cost', 'category_id']);
            $table->decimal('budget_price', 15, 2)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('eb_budget_items', function (Blueprint $table) {
            $table->dropColumn('budget_price');
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->foreignId('category_id')->nullable()->constrained('eb_budget_categories')->nullOnDelete();
        });
    }
};
