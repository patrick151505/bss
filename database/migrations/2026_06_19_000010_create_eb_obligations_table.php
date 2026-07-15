<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_obligations', function (Blueprint $table) {
            $table->id();
            $table->string('obligation_no', 50);
            $table->foreignId('fiscal_year_id')->constrained('eb_fiscal_years')->cascadeOnDelete();
            $table->foreignId('caf_id')->nullable()->constrained('eb_cafs')->nullOnDelete();
            $table->foreignId('category_id')->constrained('eb_budget_categories')->restrictOnDelete();
            $table->string('payee', 255)->nullable();
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->date('obligation_date');
            $table->enum('status', ['pending', 'fulfilled', 'partially_fulfilled', 'cancelled'])->default('pending');
            $table->decimal('fulfilled_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['fiscal_year_id', 'category_id']);
            $table->index(['fiscal_year_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_obligations');
    }
};
