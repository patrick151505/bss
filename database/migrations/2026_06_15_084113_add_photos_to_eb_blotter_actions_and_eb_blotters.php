<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_blotter_actions', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('notes');
        });

        Schema::table('eb_blotters', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('eb_blotter_actions', function (Blueprint $table) {
            $table->dropColumn('photos');
        });

        Schema::table('eb_blotters', function (Blueprint $table) {
            $table->dropColumn('photos');
        });
    }
};
