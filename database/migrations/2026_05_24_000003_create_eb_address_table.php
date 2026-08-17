<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Address / zone lookup (puroks, subdivisions) referenced by eb_citizen.address.
 * id is not auto-increment in the legacy schema (managed values).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('eb_address')) {
            return;
        }

        Schema::create('eb_address', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('description', 250);
            $table->integer('is_subd')->default(0);
            $table->string('rules_required', 250)->default('');
            // is_active is added later by 2026_06_18_163803 — don't create it here.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_address');
    }
};
