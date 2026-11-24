<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zones an account is limited to (Settings → Users). No rows = all zones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_user_zones', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('address_id');   // eb_address.id is a signed int(11)
            $table->primary(['user_id', 'address_id']);
            $table->index('address_id');
            $table->foreign('address_id')->references('id')->on('eb_address')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_user_zones');
    }
};
