<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('eb_budget_line_items', function (Blueprint $table) {
            $table->string('object_code', 20)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('eb_budget_line_items', function (Blueprint $table) {
            $table->dropColumn('object_code');
        });
    }
};
