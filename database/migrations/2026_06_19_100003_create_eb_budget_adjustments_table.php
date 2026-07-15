<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('eb_budget_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('eb_fiscal_years')->cascadeOnDelete();
            $table->foreignId('allocation_id')->constrained('eb_budget_allocations')->cascadeOnDelete();
            $table->enum('type', ['supplemental', 'realignment_in', 'realignment_out']);
            $table->decimal('amount', 15, 2); // always positive; type determines direction
            $table->string('reference_no')->nullable(); // Supplemental Budget No. / Resolution No.
            $table->date('effectivity_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_budget_adjustments');
    }
};
