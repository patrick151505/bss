<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_event', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('venue', 200)->nullable();
            $table->date('event_date');
            $table->time('event_start')->nullable();
            $table->time('event_end')->nullable();
            $table->string('cover_photo')->nullable();
            $table->tinyInteger('raffle_enabled')->default(0);
            $table->tinyInteger('allow_winner_repeat')->default(0);
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->integer('user_id');
            $table->datetime('date_created');
            $table->datetime('date_updated');
        });

        Schema::create('eb_event_attendance', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('eventId');
            $table->integer('citizenId');
            $table->datetime('time_in');
            $table->enum('method', ['qr', 'manual', 'bulk'])->default('manual');
            $table->unique(['eventId', 'citizenId']);
        });

        Schema::create('eb_event_winner', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('eventId');
            $table->integer('citizenId');
            $table->integer('round');
            $table->string('prize_label', 200)->nullable();
            $table->datetime('drawn_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_event_winner');
        Schema::dropIfExists('eb_event_attendance');
        Schema::dropIfExists('eb_event');
    }
};
