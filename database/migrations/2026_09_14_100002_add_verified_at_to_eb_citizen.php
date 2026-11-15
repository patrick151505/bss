<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A citizen counts as verified once they are a registered voter with a
     * precinct number on record, so the flag is derived rather than toggled
     * by hand. Record when that happened.
     */
    public function up(): void
    {
        Schema::table('eb_citizen', function (Blueprint $table) {
            $table->dateTime('verified_at')->nullable()->after('is_verify');
        });

        // Backfill: anyone already qualifying is verified as of now, and
        // rows previously flagged by hand keep their status with the date
        // they were last touched.
        DB::table('eb_citizen')
            ->where('voters', 1)
            ->whereNotNull('pricinct_no')
            ->where('pricinct_no', '<>', '')
            ->update([
                'is_verify'   => 1,
                'verified_at' => DB::raw('COALESCE(verified_at, date_last_updated)'),
            ]);

        DB::table('eb_citizen')
            ->where('is_verify', 1)
            ->whereNull('verified_at')
            ->update(['verified_at' => DB::raw('date_last_updated')]);
    }

    public function down(): void
    {
        Schema::table('eb_citizen', function (Blueprint $table) {
            $table->dropColumn('verified_at');
        });
    }
};
