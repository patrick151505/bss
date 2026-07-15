<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('eb_document_fields', function (Blueprint $table) {
            $table->unsignedTinyInteger('column_width')->default(12)->after('field_type'); // 1-12 (bootstrap-style grid span)
            $table->string('default_value')->nullable()->after('field_options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eb_document_fields', function (Blueprint $table) {
            $table->dropColumn(['column_width', 'default_value']);
        });
    }
};
