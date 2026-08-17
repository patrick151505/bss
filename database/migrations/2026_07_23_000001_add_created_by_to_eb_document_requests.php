<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('citizen_id');
        });
    }

    public function down(): void
    {
        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });
    }
};
