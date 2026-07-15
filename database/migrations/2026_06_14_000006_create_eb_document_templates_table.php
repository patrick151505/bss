<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');                     // e.g. "Classic Letterhead"
            $table->string('description')->nullable();
            $table->string('paper_bg')->nullable();     // uploaded background image path
            $table->string('paper_size')->default('a4');// a4, letter
            $table->string('orientation')->default('portrait'); // portrait, landscape
            // Padding (in px) — how far from edges the body content starts
            $table->unsignedSmallInteger('padding_top')->default(160);
            $table->unsignedSmallInteger('padding_bottom')->default(72);
            $table->unsignedSmallInteger('padding_left')->default(72);
            $table->unsignedSmallInteger('padding_right')->default(72);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add template_id to document_types
        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->foreignId('document_template_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('eb_document_templates')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->dropForeign(['document_template_id']);
            $table->dropColumn('document_template_id');
        });

        Schema::dropIfExists('eb_document_templates');
    }
};
