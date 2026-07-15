<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained('eb_document_types');
            $table->unsignedBigInteger('citizen_id');       // references eb_citizen.id
            $table->string('status')->default('pending');   // pending, approved, released, rejected
            $table->text('custom_fields')->nullable();       // JSON: { "purpose": "employment", ... }
            $table->boolean('is_paid')->default(false);      // copied from type at time of request
            $table->decimal('fee', 10, 2)->default(0);       // copied from type at time of request
            $table->boolean('fee_paid')->default(false);
            $table->string('or_number')->nullable();         // official receipt number
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_document_requests');
    }
};
