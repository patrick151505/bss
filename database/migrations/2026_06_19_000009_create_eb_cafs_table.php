<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_cafs', function (Blueprint $table) {
            $table->id();
            $table->string('caf_no', 50);
            $table->foreignId('fiscal_year_id')->constrained('eb_fiscal_years')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('eb_budget_categories')->nullOnDelete();
            $table->string('requested_by', 255);
            $table->decimal('amount', 15, 2);
            $table->text('purpose');
            $table->string('certified_by', 255)->nullable();
            $table->date('certified_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['pending', 'certified', 'expired', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['fiscal_year_id', 'status']);
            $table->index(['fiscal_year_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_cafs');
    }
};
