<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_fiscal_year_phase_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('eb_fiscal_years')->cascadeOnDelete();
            $table->string('from_phase', 30)->nullable();
            $table->string('to_phase', 30);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // Append-only — no updated_at

            $table->index(['fiscal_year_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_fiscal_year_phase_log');
    }
};
