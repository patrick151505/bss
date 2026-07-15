<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_blotters', function (Blueprint $table) {
            $table->unsignedBigInteger('complainant_citizen_id')->nullable()->after('complainant_contact');
            $table->unsignedBigInteger('respondent_citizen_id')->nullable()->after('respondent_contact');
        });
    }

    public function down(): void
    {
        Schema::table('eb_blotters', function (Blueprint $table) {
            $table->dropColumn(['complainant_citizen_id', 'respondent_citizen_id']);
        });
    }
};
