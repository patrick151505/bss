<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modify header table: drop category_id, add payee
        Schema::table('eb_budget_transactions', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
            $table->string('payee', 255)->nullable()->after('reference_no');
        });

        // Line items table
        Schema::create('eb_budget_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('eb_budget_transactions')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('eb_budget_categories')->restrictOnDelete();
            $table->string('particulars', 500);
            $table->decimal('quantity', 10, 3)->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('amount', 15, 2);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['transaction_id', 'sort_order'], 'ebti_tx_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_budget_transaction_items');

        Schema::table('eb_budget_transactions', function (Blueprint $table) {
            $table->dropColumn('payee');
            $table->foreignId('category_id')->nullable()->constrained('eb_budget_categories')->nullOnDelete();
        });
    }
};
