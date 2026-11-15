<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Restrict an event to residents of certain address zones (eb_address ids).
     * NULL = open to everyone. Enforced on check-in and used by the raffle's
     * area filter.
     */
    public function up(): void
    {
        Schema::table('eb_event', function (Blueprint $table) {
            $table->json('allowed_addresses')->nullable()->after('allow_winner_repeat');
        });
    }

    public function down(): void
    {
        Schema::table('eb_event', function (Blueprint $table) {
            $table->dropColumn('allowed_addresses');
        });
    }
};
