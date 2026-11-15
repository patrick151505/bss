<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Rentals now progress scheduled → out → returned.
     *
     * "Scheduled" is a booking: the items are reserved (stock is already
     * deducted so nobody double-books them) but have not been handed over.
     * "Out" means the borrower physically has them.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE eb_inventory_rentals MODIFY status ENUM('scheduled','out','returned','cancelled') NOT NULL DEFAULT 'scheduled'");

        Schema::table('eb_inventory_rentals', function (Blueprint $table) {
            // When the items actually left the barangay hall, as opposed to
            // borrowed_on, which is the date the booking is for.
            $table->timestamp('released_at')->nullable()->after('returned_on');
        });

        // Existing rentals were created under the old two-state model and
        // were already handed over the moment they were recorded.
        DB::table('eb_inventory_rentals')
            ->where('status', 'out')
            ->whereNull('released_at')
            ->update(['released_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // Fold bookings back into "out" — the old model had no other home.
        DB::table('eb_inventory_rentals')->where('status', 'scheduled')->update(['status' => 'out']);

        Schema::table('eb_inventory_rentals', function (Blueprint $table) {
            $table->dropColumn('released_at');
        });

        DB::statement("ALTER TABLE eb_inventory_rentals MODIFY status ENUM('out','returned','cancelled') NOT NULL DEFAULT 'out'");
    }
};
