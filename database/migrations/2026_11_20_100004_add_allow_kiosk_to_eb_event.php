<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Events staff open for self check-in at the barangay hall kiosk, and a
// "kiosk" check-in method so those check-ins can be told apart.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('eb_event', 'allow_kiosk')) {
            Schema::table('eb_event', function (Blueprint $table) {
                $table->boolean('allow_kiosk')->default(false)->after('allow_winner_repeat');
            });
        }
        DB::statement("ALTER TABLE eb_event_attendance MODIFY method ENUM('qr','manual','bulk','kiosk') NOT NULL DEFAULT 'manual'");
    }

    public function down(): void
    {
        DB::table('eb_event_attendance')->where('method', 'kiosk')->update(['method' => 'qr']);
        DB::statement("ALTER TABLE eb_event_attendance MODIFY method ENUM('qr','manual','bulk') NOT NULL DEFAULT 'manual'");
        if (Schema::hasColumn('eb_event', 'allow_kiosk')) {
            Schema::table('eb_event', function (Blueprint $table) {
                $table->dropColumn('allow_kiosk');
            });
        }
    }
};
