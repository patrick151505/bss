<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->string('header_style')->default('classic')->after('template_body');
            // classic  = logo center, barangay name, city/province, document title
            // side     = logo left + text right, document title below
            // minimal  = text only, no logo, centered
        });
    }

    public function down(): void
    {
        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->dropColumn('header_style');
        });
    }
};
