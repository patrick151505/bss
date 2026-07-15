<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_blotters', function (Blueprint $table) {
            $table->id();
            $table->string('blotter_no', 20)->unique();
            $table->date('filed_date');
            $table->date('incident_date');
            $table->time('incident_time')->nullable();
            $table->string('type', 50)->default('complaint');
            $table->string('incident_location')->nullable();

            // Complainant
            $table->string('complainant_name');
            $table->string('complainant_address')->nullable();
            $table->string('complainant_contact', 30)->nullable();

            // Respondent
            $table->string('respondent_name');
            $table->string('respondent_address')->nullable();
            $table->string('respondent_contact', 30)->nullable();

            // Incident details
            $table->text('narrative');
            $table->text('witnesses')->nullable();
            $table->string('attending_officer')->nullable();

            // Resolution
            $table->string('status', 20)->default('filed');
            $table->text('action_taken')->nullable();
            $table->date('settled_date')->nullable();
            $table->string('referred_to')->nullable();
            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'filed_date']);
            $table->index('incident_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_blotters');
    }
};
