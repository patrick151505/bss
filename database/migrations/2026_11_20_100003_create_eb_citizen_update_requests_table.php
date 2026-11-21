<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Detail changes a resident asked for at the kiosk. Nothing changes on the
 * citizen record until staff approve (no one checks identity at the kiosk).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_citizen_update_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('citizen_id')->index();
            $table->unsignedBigInteger('kiosk_id')->nullable();
            $table->json('changes');                         // { field: { from, to } }
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reject_reason', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_citizen_update_requests');
    }
};
