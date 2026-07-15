<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Drop old table (0 rows, structure is wrong for the new matrix design)
        Schema::dropIfExists('eb_budget_allocations');

        Schema::create('eb_budget_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('eb_fiscal_years')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('eb_budget_programs')->restrictOnDelete();
            // Each row is one cell in the matrix: program × object_class
            $table->enum('object_class', ['PS', 'MOOE', 'CO']);
            $table->decimal('appropriation', 15, 2)->default(0.00);
            // For mandatory fund rows: auto-computed amount (stored for display/audit)
            $table->decimal('computed_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // One cell per program × object_class per fiscal year
            $table->unique(['fiscal_year_id', 'program_id', 'object_class'], 'alloc_fy_prog_obj_unique');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_budget_allocations');
    }
};
