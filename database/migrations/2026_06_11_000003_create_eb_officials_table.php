<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_officials', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('position', 255);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('eb_settings', function (Blueprint $table) {
            $table->dropColumn('secretary_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_officials');

        Schema::table('eb_settings', function (Blueprint $table) {
            $table->string('secretary_name', 255)->nullable();
        });
    }
};
