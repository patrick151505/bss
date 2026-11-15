<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * BBPF02 has a fourth expense class — Financial Expenses (5-03-xx:
     * Interest Expenses, Bank Charges, Other Financial Charges) — with its
     * own subtotal on the printed form. Widen both enums to accept it.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE eb_budget_line_items  MODIFY object_class ENUM('PS','MOOE','FE','CO') NOT NULL");
        DB::statement("ALTER TABLE eb_budget_allocations MODIFY object_class ENUM('PS','MOOE','FE','CO') NOT NULL");
    }

    public function down(): void
    {
        DB::table('eb_budget_line_items')->where('object_class', 'FE')->delete();
        DB::table('eb_budget_allocations')->where('object_class', 'FE')->delete();

        DB::statement("ALTER TABLE eb_budget_line_items  MODIFY object_class ENUM('PS','MOOE','CO') NOT NULL");
        DB::statement("ALTER TABLE eb_budget_allocations MODIFY object_class ENUM('PS','MOOE','CO') NOT NULL");
    }
};
