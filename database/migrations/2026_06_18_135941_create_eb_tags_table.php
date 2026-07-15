<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('eb_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 7)->default('#6366f1');
            $table->timestamps();
        });

        Schema::create('eb_citizen_tag', function (Blueprint $table) {
            $table->integer('citizen_id');
            $table->unsignedBigInteger('tag_id');
            $table->primary(['citizen_id', 'tag_id']);
            $table->foreign('tag_id')->references('id')->on('eb_tags')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_citizen_tag');
        Schema::dropIfExists('eb_tags');
    }
};
