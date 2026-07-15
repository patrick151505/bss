<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('eb_income_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('eb_fiscal_years')->cascadeOnDelete();
            // Source type — determines which income pool it belongs to
            $table->enum('source_type', ['nta', 'rpt', 'clearance', 'other'])->default('other');
            $table->string('source_label', 255); // display name e.g. "Barangay Clearance Fees"
            $table->decimal('estimated_amount', 15, 2)->default(0.00);
            $table->decimal('prior_year_actual', 15, 2)->nullable(); // for reference
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'source_label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_income_estimates');
    }
};
