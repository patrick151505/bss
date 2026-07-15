<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // e.g. "Barangay Clearance"
            $table->string('short_name')->nullable();        // e.g. "Clearance"
            $table->text('description')->nullable();
            $table->boolean('is_paid')->default(false);      // paid or free
            $table->decimal('fee', 10, 2)->default(0);       // fee amount if paid
            $table->boolean('requires_approval')->default(true);
            $table->longText('template_body')->nullable();   // HTML/text with {{ placeholders }}
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_document_types');
    }
};
