<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Most barangay inventory is consumable — released and not returned.
     * A few items (tents, chairs, sound systems) are lent out and expected
     * back, so flag those explicitly. Defaults to not rentable.
     */
    public function up(): void
    {
        Schema::table('eb_inventory_items', function (Blueprint $table) {
            $table->boolean('is_rentable')->default(false)->after('requires_approval');
        });
    }

    public function down(): void
    {
        Schema::table('eb_inventory_items', function (Blueprint $table) {
            $table->dropColumn('is_rentable');
        });
    }
};
