<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_budget_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('tin', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('contact_person', 255)->nullable();
            $table->string('contact_no', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('eb_budget_transactions', function (Blueprint $table) {
            $table->foreignId('supplier_id')
                ->nullable()
                ->after('payee')
                ->constrained('eb_budget_suppliers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('eb_budget_transactions', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\BudgetSupplier::class, 'supplier_id');
            $table->dropColumn('supplier_id');
        });

        Schema::dropIfExists('eb_budget_suppliers');
    }
};
