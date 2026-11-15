<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Manual reopen: when staff reopen an event after its scheduled end, it stays
     * open (check-in allowed) until they close it again, instead of being
     * auto-closed straight away by the end time.
     */
    public function up(): void
    {
        Schema::table('eb_event', function (Blueprint $table) {
            $table->dateTime('reopened_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('eb_event', function (Blueprint $table) {
            $table->dropColumn('reopened_at');
        });
    }
};
