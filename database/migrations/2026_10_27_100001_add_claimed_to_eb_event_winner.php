<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Track whether a raffle winner has claimed their prize, when, and which
     * staff member handed it over.
     */
    public function up(): void
    {
        Schema::table('eb_event_winner', function (Blueprint $table) {
            $table->dateTime('claimed_at')->nullable()->after('drawn_at');
            $table->unsignedBigInteger('claimed_by')->nullable()->after('claimed_at');
        });
    }

    public function down(): void
    {
        Schema::table('eb_event_winner', function (Blueprint $table) {
            $table->dropColumn(['claimed_at', 'claimed_by']);
        });
    }
};
