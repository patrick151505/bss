<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->foreignId('document_template_version_id')
                  ->nullable()
                  ->after('document_template_id')
                  ->constrained('eb_document_template_versions')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->dropForeign(['document_template_version_id']);
            $table->dropColumn('document_template_version_id');
        });
    }
};
