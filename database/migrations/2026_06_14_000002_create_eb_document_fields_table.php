<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_document_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained('eb_document_types')->cascadeOnDelete();
            $table->string('field_key');        // e.g. "purpose" → used as {{ purpose }}
            $table->string('field_label');      // e.g. "Purpose of Request"
            $table->string('field_type')->default('text'); // text, textarea, select, date
            $table->text('field_options')->nullable();     // JSON for select options
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_document_fields');
    }
};
