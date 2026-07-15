<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_budget_items', function (Blueprint $table) {
            $table->enum('type', ['flat', 'collection'])->default('flat')->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('eb_budget_items', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
