<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_appropriations_ordinances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('eb_fiscal_years')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('eb_appropriations_ordinances')->nullOnDelete();
            $table->string('ordinance_no', 50);
            $table->unsignedTinyInteger('supplemental_no')->nullable();
            $table->date('ordinance_date')->nullable();
            $table->date('session_date')->nullable();
            $table->string('enacted_by', 255)->nullable();
            $table->longText('resolution_text')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', [
                'draft',
                'enacted',
                'submitted_for_review',
                'approved',
                'inoperative',
            ])->default('draft');
            $table->timestamp('review_submitted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('fiscal_year_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_appropriations_ordinances');
    }
};
