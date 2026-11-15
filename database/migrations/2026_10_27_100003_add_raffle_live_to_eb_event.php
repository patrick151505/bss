<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Live raffle state shared between the staff controller screen and the
     * audience display (spin → presence check → winners → idle). The version
     * counter lets the display poll cheaply and react only to new steps.
     */
    public function up(): void
    {
        Schema::table('eb_event', function (Blueprint $table) {
            $table->longText('raffle_live')->nullable()->after('raffle_pin_expires_at');
            $table->unsignedInteger('raffle_live_ver')->default(0)->after('raffle_live');
        });
    }

    public function down(): void
    {
        Schema::table('eb_event', function (Blueprint $table) {
            $table->dropColumn(['raffle_live', 'raffle_live_ver']);
        });
    }
};
