<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ordinances & Resolutions passed by the Sangguniang Barangay.
 *   eb_legislations         one row per ordinance / resolution
 *   eb_legislation_authors  authors, co-authors and sponsors (an official, or a typed name)
 *   eb_legislation_events   legislative timeline (introduced, readings, approval, posting…)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('eb_legislations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);                 // ordinance | resolution
            $table->string('number', 50);               // e.g. 2026-003
            $table->unsignedSmallInteger('series_year');
            $table->string('title', 500);
            $table->text('summary')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('status', 30)->default('draft');
            $table->date('date_introduced')->nullable();
            $table->date('date_approved')->nullable();
            $table->date('date_effective')->nullable();
            $table->unsignedSmallInteger('votes_yes')->nullable();
            $table->unsignedSmallInteger('votes_no')->nullable();
            $table->unsignedSmallInteger('votes_abstain')->nullable();
            $table->foreignId('amends_id')->nullable()->constrained('eb_legislations')->nullOnDelete();
            $table->foreignId('repealed_by_id')->nullable()->constrained('eb_legislations')->nullOnDelete();
            $table->string('file_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['type', 'number']);
            $table->index(['status', 'series_year']);
        });

        Schema::create('eb_legislation_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legislation_id')->constrained('eb_legislations')->cascadeOnDelete();
            $table->foreignId('official_id')->nullable()->constrained('eb_officials')->nullOnDelete();
            $table->string('name', 255);
            $table->string('role', 20)->default('author'); // author | co_author | sponsor
            $table->timestamps();
        });

        Schema::create('eb_legislation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legislation_id')->constrained('eb_legislations')->cascadeOnDelete();
            $table->string('event', 30);
            $table->date('event_date');
            $table->text('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_legislation_events');
        Schema::dropIfExists('eb_legislation_authors');
        Schema::dropIfExists('eb_legislations');
    }
};
