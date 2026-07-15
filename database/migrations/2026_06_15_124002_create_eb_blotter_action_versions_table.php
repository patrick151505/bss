<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('eb_blotter_action_versions', function (Blueprint $table) {
            $table->id();
            $table->integer('action_id')->index();
            $table->integer('blotter_id')->index();
            $table->string('outcome', 30);
            $table->json('complainant_attendance')->nullable();
            $table->json('respondent_attendance')->nullable();
            $table->longText('notes')->nullable();
            $table->json('photos')->nullable();
            $table->integer('saved_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eb_blotter_action_versions');
    }
};
