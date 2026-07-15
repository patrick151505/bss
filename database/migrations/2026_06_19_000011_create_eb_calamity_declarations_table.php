<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_calamity_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('eb_fiscal_years')->cascadeOnDelete();
            $table->string('declaration_no', 100);
            $table->date('declared_date');
            $table->string('declaring_authority', 255);
            $table->string('disaster_type', 100);
            $table->string('affected_area', 255)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'lifted'])->default('active');
            $table->date('lifted_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['fiscal_year_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_calamity_declarations');
    }
};
