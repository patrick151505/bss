<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('eb_budget_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('eb_fiscal_years')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('eb_budget_programs')->restrictOnDelete();
            $table->enum('object_class', ['PS', 'MOOE', 'CO']);
            $table->string('name', 255);
            $table->decimal('appropriation', 15, 2)->default(0.00);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            // A line item name must be unique within the same program+object_class+fiscal_year
            $table->unique(
                ['fiscal_year_id', 'program_id', 'object_class', 'name'],
                'line_items_fy_prog_cls_name_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_budget_line_items');
    }
};
