<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Name of the house owner, for residents who rent (owner_status = 0).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('eb_citizen', 'house_owner_name')) {
            Schema::table('eb_citizen', function (Blueprint $table) {
                $table->string('house_owner_name', 150)->nullable()->after('owner_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('eb_citizen', 'house_owner_name')) {
            Schema::table('eb_citizen', function (Blueprint $table) {
                $table->dropColumn('house_owner_name');
            });
        }
    }
};
