<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_budget_transactions', function (Blueprint $table) {
            // Stores the BIR withholding tax classification at time of voucher creation
            $table->string('tax_type', 30)->nullable()->after('tax_withheld');
        });
    }

    public function down(): void
    {
        Schema::table('eb_budget_transactions', function (Blueprint $table) {
            $table->dropColumn('tax_type');
        });
    }
};
