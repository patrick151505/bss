<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_budget_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('eb_fiscal_years')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('eb_budget_categories')->cascadeOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->string('reference_no', 100)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['fiscal_year_id', 'type', 'transaction_date'], 'ebt_fy_type_date_idx');
            $table->index(['fiscal_year_id', 'category_id'], 'ebt_fy_cat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_budget_transactions');
    }
};
