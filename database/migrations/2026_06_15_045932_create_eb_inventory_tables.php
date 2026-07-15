<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Categories (Medicine, Relief Goods, Equipment, Supplies…)
        Schema::create('eb_inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable()->default('mgc_box_line');
            $table->string('color')->nullable()->default('primary');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Items (what is stocked)
        Schema::create('eb_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('eb_inventory_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('unit')->default('pcs');
            $table->text('description')->nullable();
            $table->integer('stock')->default(0);
            $table->integer('low_stock_threshold')->default(10);
            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Stock-in log (receiving inventory)
        Schema::create('eb_inventory_stock_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('eb_inventory_items')->cascadeOnDelete();
            $table->integer('quantity');
            $table->string('source')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Releases to citizens
        Schema::create('eb_inventory_releases', function (Blueprint $table) {
            $table->id();
            $table->integer('citizen_id');
            $table->foreign('citizen_id')->references('id')->on('eb_citizen')->cascadeOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('purpose')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        // Line items per release
        Schema::create('eb_inventory_release_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('release_id')->constrained('eb_inventory_releases')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('eb_inventory_items')->cascadeOnDelete();
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_inventory_release_items');
        Schema::dropIfExists('eb_inventory_releases');
        Schema::dropIfExists('eb_inventory_stock_ins');
        Schema::dropIfExists('eb_inventory_items');
        Schema::dropIfExists('eb_inventory_categories');
    }
};
