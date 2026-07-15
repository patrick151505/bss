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
        Schema::create('eb_citizen_ids', function (Blueprint $table) {
            $table->id();
            $table->integer('citizen_id');
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->date('valid_until');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eb_citizen_ids');
    }
};
