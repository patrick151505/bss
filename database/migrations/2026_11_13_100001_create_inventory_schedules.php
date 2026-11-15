<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Inventory schedule: date/time reservations of items such as the ambulance,
     * the multi-purpose hall or a service vehicle, shown on a calendar. Only items
     * marked "Can be scheduled" can be reserved; an item is never booked beyond
     * its stock for overlapping times.
     */
    public function up(): void
    {
        Schema::table('eb_inventory_items', function (Blueprint $table) {
            $table->boolean('is_schedulable')->default(false)->after('is_rentable');
        });

        Schema::create('eb_inventory_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('citizen_id')->nullable();      // registered resident it's for
            $table->string('requester_name', 150)->nullable();         // or a typed name
            $table->string('contact', 30)->nullable();
            $table->string('purpose', 255);
            $table->string('location', 255)->nullable();                // destination / venue
            $table->text('notes')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('status', 20)->default('reserved');         // reserved | completed | cancelled
            $table->string('cancel_reason', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['item_id', 'status', 'start_at', 'end_at']);
            $table->index('citizen_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_inventory_schedules');
        Schema::table('eb_inventory_items', function (Blueprint $table) {
            $table->dropColumn('is_schedulable');
        });
    }
};
