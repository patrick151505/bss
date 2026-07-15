<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_budget_items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->nullable()->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->foreignId('category_id')->nullable()->constrained('eb_budget_categories')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'name'], 'ebbi_active_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_budget_items');
    }
};
