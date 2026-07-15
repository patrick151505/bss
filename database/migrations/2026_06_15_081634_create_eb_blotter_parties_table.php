<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_blotter_parties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blotter_id');
            $table->enum('role', ['complainant', 'respondent']);
            $table->integer('citizen_id')->nullable();  // int(11) to match eb_citizen.id — no FK constraint (legacy schema)
            $table->string('name', 255);
            $table->string('address', 255)->nullable();
            $table->string('contact', 30)->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('blotter_id')->references('id')->on('eb_blotters')->cascadeOnDelete();
            $table->index(['blotter_id', 'role']);
            $table->index('citizen_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_blotter_parties');
    }
};
