<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('eb_liquidation_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_advance_id')->unique()->constrained('eb_cash_advances')->restrictOnDelete();
            $table->date('liquidation_date');
            $table->decimal('total_expenses', 15, 2)->default(0); // sum of lines
            $table->decimal('refund_amount', 15, 2)->default(0);  // cash returned
            $table->string('refund_or_no')->nullable();           // OR number for refund
            // Invariant: amount_granted - total_expenses - refund_amount = 0 to close
            $table->enum('status', ['draft', 'closed'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('eb_liquidation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidation_report_id')->constrained('eb_liquidation_reports')->cascadeOnDelete();
            $table->string('or_no')->nullable();        // Official Receipt number
            $table->date('receipt_date')->nullable();
            $table->string('particulars');
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_liquidation_lines');
        Schema::dropIfExists('eb_liquidation_reports');
    }
};
