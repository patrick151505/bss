<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Managed list of selectable purposes for document requests.
        Schema::create('eb_document_purposes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // The purpose chosen for each request (always required going forward).
        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->string('purpose')->nullable()->after('citizen_id');
        });
    }

    public function down(): void
    {
        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });

        Schema::dropIfExists('eb_document_purposes');
    }
};
