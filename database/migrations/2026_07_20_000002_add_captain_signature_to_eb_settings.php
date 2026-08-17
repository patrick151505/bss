<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Barangay Captain's signature image (used on certificates/documents).
        Schema::table('eb_settings', function (Blueprint $table) {
            $table->string('captain_signature')->nullable()->after('captain_position');
        });
    }

    public function down(): void
    {
        Schema::table('eb_settings', function (Blueprint $table) {
            $table->dropColumn('captain_signature');
        });
    }
};
