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
        Schema::table('eb_inventory_stock_ins', function (Blueprint $table) {
            // 'stock_in' = normal receive, 'adjustment' = manual count correction
            $table->string('type')->default('stock_in')->after('id');
            $table->integer('quantity_before')->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('eb_inventory_stock_ins', function (Blueprint $table) {
            $table->dropColumn(['type', 'quantity_before']);
        });
    }
};
