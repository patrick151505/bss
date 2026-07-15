<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_budget_transactions', function (Blueprint $table) {
            $table->string('bank_name', 100)->nullable()->after('check_date');
            $table->string('or_number', 50)->nullable()->after('bank_name');
            $table->date('or_date')->nullable()->after('or_number');
        });
    }

    public function down(): void
    {
        Schema::table('eb_budget_transactions', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'or_number', 'or_date']);
        });
    }
};
