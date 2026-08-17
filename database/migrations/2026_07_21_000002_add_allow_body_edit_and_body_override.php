<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-type toggle: may staff edit the certificate body when issuing?
        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->boolean('allow_body_edit')->default(false)->after('template_body');
        });

        // The per-request edited certificate body (used instead of the type's
        // template_body when the type allows editing and staff changed it).
        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->longText('body_override')->nullable()->after('custom_fields');
        });
    }

    public function down(): void
    {
        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->dropColumn('allow_body_edit');
        });
        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->dropColumn('body_override');
        });
    }
};
