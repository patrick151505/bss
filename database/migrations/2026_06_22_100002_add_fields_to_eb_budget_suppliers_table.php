<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_budget_suppliers', function (Blueprint $table) {
            $table->string('zip_code', 10)->nullable()->after('address');
            $table->string('line_of_business', 255)->nullable()->after('zip_code');
            $table->enum('business_type', ['individual', 'corporation', 'na'])->default('na')->after('line_of_business');
            $table->enum('vat_type', ['vat', 'non_vat', 'exempt'])->default('non_vat')->after('business_type');
            $table->enum('provides', ['goods', 'services', 'both'])->default('goods')->after('vat_type');
        });
    }

    public function down(): void
    {
        Schema::table('eb_budget_suppliers', function (Blueprint $table) {
            $table->dropColumn(['zip_code', 'line_of_business', 'business_type', 'vat_type', 'provides']);
        });
    }
};
