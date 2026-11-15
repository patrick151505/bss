<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing varchar rows → JSON keyed by party index
        foreach (DB::table('eb_blotter_actions')->get() as $row) {
            DB::table('eb_blotter_actions')->where('id', $row->id)->update([
                'complainant_attendance' => json_encode(['0' => $row->complainant_attendance ?? 'present']),
                'respondent_attendance'  => json_encode(['0' => $row->respondent_attendance  ?? 'present']),
            ]);
        }

        Schema::table('eb_blotter_actions', function (Blueprint $table) {
            // No default: MySQL doesn't allow one on JSON/TEXT columns (MariaDB does).
            // BlotterController always sets both values when saving an action.
            $table->json('complainant_attendance')->change();
            $table->json('respondent_attendance')->change();
        });
    }

    public function down(): void
    {
        Schema::table('eb_blotter_actions', function (Blueprint $table) {
            $table->string('complainant_attendance', 20)->default('present')->change();
            $table->string('respondent_attendance', 20)->default('present')->change();
        });
    }
};
