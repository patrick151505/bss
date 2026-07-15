<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_budget_transactions', function (Blueprint $table) {
            $table->string('payee_tin', 50)->nullable()->after('payee');
            $table->text('payee_address')->nullable()->after('payee_tin');
            $table->string('payee_zip_code', 10)->nullable()->after('payee_address');
        });
    }

    public function down(): void
    {
        Schema::table('eb_budget_transactions', function (Blueprint $table) {
            $table->dropColumn(['payee_tin', 'payee_address', 'payee_zip_code']);
        });
    }
};
