<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Civil status lookup (Single, Married, Widowed, …).
 * Legacy base table — originally created outside migrations; added so a fresh
 * (SaaS tenant) database can be built from `php artisan migrate`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('eb_civil')) {
            return; // already present (e.g. imported DB) — don't clobber
        }

        Schema::create('eb_civil', function (Blueprint $table) {
            $table->increments('id');
            $table->string('description', 250);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_civil');
    }
};
