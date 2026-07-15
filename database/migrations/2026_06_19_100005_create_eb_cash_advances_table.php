<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('eb_cash_advances', function (Blueprint $table) {
            $table->id();
            $table->string('ca_no')->unique(); // auto-generated e.g. CA-2026-001
            $table->foreignId('fiscal_year_id')->constrained('eb_fiscal_years')->restrictOnDelete();
            $table->foreignId('officer_id')->constrained('eb_accountable_officers')->restrictOnDelete();
            $table->foreignId('allocation_id')->nullable()->constrained('eb_budget_allocations')->nullOnDelete();
            $table->string('purpose');
            $table->decimal('amount', 15, 2);
            $table->date('date_granted');
            $table->date('deadline_date'); // COA: 2 months in-station, 3 out
            $table->enum('status', ['open', 'liquidated', 'cancelled'])->default('open');
            $table->string('approved_by')->nullable();
            $table->string('reference_no')->nullable(); // DV No. of the CA
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_cash_advances');
    }
};
