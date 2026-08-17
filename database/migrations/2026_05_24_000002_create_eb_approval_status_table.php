<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Approval status lookup (Pending / Approved / Rejected).
 * IDs are fixed values referenced by eb_citizen.approval_status, so this table
 * uses a non-auto-increment id (values are seeded explicitly).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('eb_approval_status')) {
            return;
        }

        Schema::create('eb_approval_status', function (Blueprint $table) {
            $table->integer('id')->primary();     // fixed ids, not auto-increment
            $table->string('description', 250);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_approval_status');
    }
};
