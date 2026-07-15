<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_document_types', function (Blueprint $table) {
            // Path to uploaded paper background image (stored in storage/app/public/documents/)
            $table->string('paper_bg')->nullable()->after('header_style');
        });
    }

    public function down(): void
    {
        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->dropColumn('paper_bg');
        });
    }
};
