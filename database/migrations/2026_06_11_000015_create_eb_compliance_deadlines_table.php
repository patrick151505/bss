<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_compliance_deadlines', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category', 30)->default('other'); // reporting, auditing, procurement, budget_prep, tax, other
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->unsignedBigInteger('fiscal_year_id')->nullable();
            $table->string('recurrence', 20)->default('none'); // none, monthly, quarterly, annual
            $table->string('status', 20)->default('pending'); // pending, completed, dismissed
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_date']);
            $table->index('fiscal_year_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_compliance_deadlines');
    }
};
