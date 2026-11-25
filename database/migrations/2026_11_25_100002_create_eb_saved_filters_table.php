<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Named filter combinations staff save on a list page (Citizens for now),
 * e.g. "Purok 1 · Seniors · Voters". `query` is the list's query string.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('eb_saved_filters')) {
            return;
        }
        Schema::create('eb_saved_filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('module', 40)->default('citizens');
            $table->string('name', 80);
            $table->text('query');
            $table->boolean('is_shared')->default(false);
            $table->timestamps();
            $table->index(['module', 'is_shared']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_saved_filters');
    }
};
