<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_ordinance_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordinance_id')->constrained('eb_appropriations_ordinances')->cascadeOnDelete();
            $table->foreignId('official_id')->constrained('eb_officials')->restrictOnDelete();
            $table->enum('vote', ['yes', 'no', 'abstain', 'absent']);
            $table->text('remarks')->nullable();
            $table->timestamp('voted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['ordinance_id', 'official_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_ordinance_votes');
    }
};
