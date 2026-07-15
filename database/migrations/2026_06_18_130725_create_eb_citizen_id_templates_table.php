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
        Schema::create('eb_citizen_id_templates', function (Blueprint $table) {
            $table->id();
            // Front side
            $table->enum('orientation_front', ['landscape', 'portrait'])->default('landscape');
            $table->string('bg_front')->nullable();
            $table->longText('html_front')->nullable();
            $table->longText('css_front')->nullable();
            // Back side
            $table->enum('orientation_back', ['landscape', 'portrait'])->default('landscape');
            $table->string('bg_back')->nullable();
            $table->longText('html_back')->nullable();
            $table->longText('css_back')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eb_citizen_id_templates');
    }
};
