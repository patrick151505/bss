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
        Schema::create('eb_blotter_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blotter_id');
            $table->string('action_type', 50);   // hearing, summons, mediation, conciliation, other
            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->string('venue')->nullable();
            $table->string('conducted_by')->nullable();
            $table->string('outcome', 30)->default('pending'); // pending, held, cancelled, rescheduled
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('blotter_id')->references('id')->on('eb_blotters')->onDelete('cascade');
            $table->index(['blotter_id', 'scheduled_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eb_blotter_actions');
    }
};
