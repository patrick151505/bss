<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_event', function (Blueprint $table) {
            $table->string('raffle_pin', 6)->nullable()->after('allow_winner_repeat');
            $table->datetime('raffle_pin_expires_at')->nullable()->after('raffle_pin');
        });
    }

    public function down(): void
    {
        Schema::table('eb_event', function (Blueprint $table) {
            $table->dropColumn(['raffle_pin', 'raffle_pin_expires_at']);
        });
    }
};
