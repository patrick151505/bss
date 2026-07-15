<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_budget_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('eb_fiscal_years')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('eb_budget_categories')->cascadeOnDelete();
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_budget_allocations');
    }
};
