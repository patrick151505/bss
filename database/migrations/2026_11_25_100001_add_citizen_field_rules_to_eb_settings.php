<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settings → Citizen Fields: which citizen fields are required / unique.
 * NULL = the built-in defaults (see App\Support\CitizenFieldRules).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('eb_settings', 'citizen_field_rules')) {
            Schema::table('eb_settings', function (Blueprint $table) {
                $table->json('citizen_field_rules')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('eb_settings', 'citizen_field_rules')) {
            Schema::table('eb_settings', function (Blueprint $table) {
                $table->dropColumn('citizen_field_rules');
            });
        }
    }
};
