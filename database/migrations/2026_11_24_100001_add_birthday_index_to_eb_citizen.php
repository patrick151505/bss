<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Birthdays page speed: MONTH(bday)/DAY(bday) cannot use an index, so every
 * birthday query read the whole citizen table. These two columns are kept in
 * sync by the database itself (generated from bday) and are indexed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('eb_citizen', 'bday_month')) {
            return;
        }

        Schema::table('eb_citizen', function (Blueprint $table) {
            $table->unsignedTinyInteger('bday_month')->nullable()->storedAs('MONTH(`bday`)');
            $table->unsignedTinyInteger('bday_day')->nullable()->storedAs('DAYOFMONTH(`bday`)');
            // Includes the columns the page filters and counts on, so the counts
            // are answered from the index alone (citizen rows are wide).
            $table->index(['bday_month', 'bday_day', 'is_active', 'is_walkin', 'voters', 'gender', 'address', 'bday'], 'eb_citizen_bday_md_index');
        });

        // Refresh index statistics so MySQL picks the new index right away.
        DB::statement('ANALYZE TABLE eb_citizen');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('eb_citizen', 'bday_month')) {
            return;
        }

        Schema::table('eb_citizen', function (Blueprint $table) {
            $table->dropIndex('eb_citizen_bday_md_index');
            $table->dropColumn(['bday_month', 'bday_day']);
        });
    }
};
