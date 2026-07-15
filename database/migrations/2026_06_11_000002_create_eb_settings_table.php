<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_settings', function (Blueprint $table) {
            $table->id();
            $table->string('barangay_name', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('municipality', 255)->nullable();
            $table->string('province', 255)->nullable();
            $table->string('contact', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('logo', 500)->nullable();
            $table->string('captain_name', 255)->nullable();
            $table->string('captain_position', 100)->default('Barangay Captain');
            $table->string('secretary_name', 255)->nullable();
            $table->timestamps();
        });

        // Seed the single settings row
        DB::table('eb_settings')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_settings');
    }
};
