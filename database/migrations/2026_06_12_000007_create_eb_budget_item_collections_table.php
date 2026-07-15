<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_budget_item_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_item_id')->constrained('eb_budget_items')->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('or_ref_no', 100)->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('collected_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('budget_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_budget_item_collections');
    }
};
