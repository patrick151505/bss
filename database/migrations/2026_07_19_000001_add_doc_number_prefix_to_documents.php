<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-type control-number prefix, e.g. "BRG" → BRG-00001, BRG-00002 …
        // Required and unique: every document type owns a distinct prefix.
        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->string('prefix', 20)->after('short_name');
            $table->unique('prefix');
        });

        // The sequential number assigned to a request when it is created.
        // Sequence is per document_type_id (each type has its own counter).
        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->unsignedInteger('doc_number')->nullable()->after('document_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->dropUnique(['prefix']);
            $table->dropColumn('prefix');
        });

        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->dropColumn('doc_number');
        });
    }
};
