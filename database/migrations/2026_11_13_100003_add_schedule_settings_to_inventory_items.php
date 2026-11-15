<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Per-item schedule settings:
     *  - schedule_color: the item's color on the Schedule calendar (#rrggbb; NULL = automatic)
     *  - schedule_buffer_days: days kept free after each reservation (return, cleaning,
     *    checking) before the item can be reserved again. Default 3.
     */
    public function up(): void
    {
        Schema::table('eb_inventory_items', function (Blueprint $table) {
            $table->string('schedule_color', 7)->nullable()->after('is_schedulable');
            $table->unsignedSmallInteger('schedule_buffer_days')->default(3)->after('schedule_color');
        });
    }

    public function down(): void
    {
        Schema::table('eb_inventory_items', function (Blueprint $table) {
            $table->dropColumn(['schedule_color', 'schedule_buffer_days']);
        });
    }
};
