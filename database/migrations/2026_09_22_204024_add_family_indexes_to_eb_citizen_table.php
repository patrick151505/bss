<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opening a household read ~160ms because fetching a family's members had no
 * usable index: MySQL fell back to the `is_active` index (cardinality 4) and
 * scanned ~11,700 rows to return a handful.
 *
 * The queries this serves:
 *   families.members -> where familyId IN (..) and isHead = 0 and is_active = 1
 *   families.head    -> where familyId = ?     and isHead <> 0
 *   household list   -> where familyId IN (..) and isHead = 0   (grouped count)
 *
 * familyId leads because it is the selective column; isHead and is_active
 * follow so the whole predicate is covered by one index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_citizen', function (Blueprint $table) {
            $table->index(['familyId', 'isHead', 'is_active'], 'eb_citizen_family_head_active_index');

            // The citizen picker counts "active + approved" on every open and
            // every keystroke. Alone, is_active has cardinality 4, so MySQL
            // scanned ~11,700 rows for the COUNT.
            $table->index(['is_active', 'approval_status'], 'eb_citizen_active_approval_index');
        });
    }

    public function down(): void
    {
        Schema::table('eb_citizen', function (Blueprint $table) {
            $table->dropIndex('eb_citizen_family_head_active_index');
            $table->dropIndex('eb_citizen_active_approval_index');
        });
    }
};
