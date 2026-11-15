<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Use for medicine" on inventory categories: only items in these categories
 * can be given as medicine at a Health check-up.
 *
 * Existing categories whose name clearly means medicine (Medical Supplies,
 * Medicines, Pharmacy…) are switched on so the Health picker keeps working;
 * everything else stays off and can be switched on in Inventory → Categories.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('eb_inventory_categories', function (Blueprint $table) {
            $table->boolean('is_medicine')->default(false)->after('is_active');
        });

        DB::table('eb_inventory_categories')
            ->where(fn($q) => $q->where('name', 'like', '%medic%')
                ->orWhere('name', 'like', '%pharma%')
                ->orWhere('name', 'like', '%gamot%')
                ->orWhere('name', 'like', '%drug%'))
            ->update(['is_medicine' => true]);
    }

    public function down(): void
    {
        Schema::table('eb_inventory_categories', function (Blueprint $table) {
            $table->dropColumn('is_medicine');
        });
    }
};
