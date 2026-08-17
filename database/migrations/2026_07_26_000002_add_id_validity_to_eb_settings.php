<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_settings', function (Blueprint $table) {
            // Barangay-wide default validity period for generated IDs: 6m / 1y / 2y.
            $table->string('id_validity', 4)->default('2y')->after('citizen_id_digits');
        });
    }

    public function down(): void
    {
        Schema::table('eb_settings', function (Blueprint $table) {
            $table->dropColumn('id_validity');
        });
    }
};
