<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Speed up the daily citizen search (scanner exact-match on qrcode, the
        // is_active filter on every query, and the lname/fname ordering).
        Schema::table('eb_citizen', function ($table) {
            $table->index('qrcode', 'eb_citizen_qrcode_index');
            $table->index('is_active', 'eb_citizen_is_active_index');
            $table->index(['lname', 'fname'], 'eb_citizen_lname_fname_index');
        });
    }

    public function down(): void
    {
        Schema::table('eb_citizen', function ($table) {
            $table->dropIndex('eb_citizen_qrcode_index');
            $table->dropIndex('eb_citizen_is_active_index');
            $table->dropIndex('eb_citizen_lname_fname_index');
        });
    }
};
