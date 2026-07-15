<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('eb_income_estimates', function (Blueprint $table) {
            $table->decimal('two_years_ago_actual', 15, 2)->nullable()->after('estimated_amount');
        });
    }

    public function down(): void
    {
        Schema::table('eb_income_estimates', function (Blueprint $table) {
            $table->dropColumn('two_years_ago_actual');
        });
    }
};
