<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Rentals: items lent to a citizen and expected back, as opposed to
     * releases, which hand over consumables for good.
     */
    public function up(): void
    {
        Schema::create('eb_inventory_rentals', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('citizen_id');
            $table->foreignId('released_by')->constrained('users')->restrictOnDelete();

            // out = with the borrower, returned = all items back,
            // cancelled = never actually handed over.
            $table->enum('status', ['out', 'returned', 'cancelled'])->default('out');

            $table->date('borrowed_on');
            $table->date('due_on');
            $table->date('returned_on')->nullable();

            $table->string('purpose', 255)->nullable();
            $table->text('remarks')->nullable();

            // Money side — a rental may carry a usage fee, a refundable
            // deposit, or neither.
            $table->decimal('rental_fee', 12, 2)->default(0);
            $table->decimal('deposit', 12, 2)->default(0);
            $table->boolean('fee_paid')->default(false);
            $table->boolean('deposit_refunded')->default(false);

            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('citizen_id');
            $table->index(['status', 'due_on']);
        });

        Schema::create('eb_inventory_rental_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('eb_inventory_rentals')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('eb_inventory_items')->restrictOnDelete();

            $table->unsignedInteger('quantity');
            // Items can come back in batches, so track returns per line.
            $table->unsignedInteger('quantity_returned')->default(0);

            $table->enum('condition_out', ['good', 'fair', 'damaged'])->default('good');
            $table->enum('condition_in', ['good', 'fair', 'damaged', 'lost'])->nullable();
            $table->text('condition_notes')->nullable();

            $table->timestamps();

            $table->index(['rental_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_inventory_rental_items');
        Schema::dropIfExists('eb_inventory_rentals');
    }
};
