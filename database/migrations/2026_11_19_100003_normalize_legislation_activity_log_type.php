<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ordinance & resolution log entries were saved as "Legislation"; every other
 * module uses a lowercase type. Rename them so Activity Logs → Module →
 * Ordinances & Resolutions finds the older entries too.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('eb_activity_logs')->where('subject_type', 'Legislation')->update(['subject_type' => 'legislation']);
    }

    public function down(): void
    {
        DB::table('eb_activity_logs')->where('subject_type', 'legislation')->update(['subject_type' => 'Legislation']);
    }
};
