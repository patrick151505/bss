<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_ordinance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordinance_id')->constrained('eb_appropriations_ordinances')->cascadeOnDelete();
            $table->string('reviewing_body', 255);
            $table->date('review_date')->nullable();
            $table->enum('status', ['submitted', 'returned', 'approved', 'declared_inoperative'])->default('submitted');
            $table->text('comments')->nullable();
            $table->text('returned_reason')->nullable();
            $table->date('declared_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('ordinance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_ordinance_reviews');
    }
};
