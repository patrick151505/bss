<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_budget_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('eb_budget_categories')->nullOnDelete();
            $table->string('name', 255);
            $table->enum('type', ['income', 'expense']);
            $table->string('code', 50)->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'parent_id', 'sort_order']);
        });

        // Seed standard barangay categories
        $now = now();

        // ── Income parent categories ──
        DB::table('eb_budget_categories')->insert([
            ['id' => 1,  'parent_id' => null, 'name' => 'Internal Revenue Allotment (IRA)',  'type' => 'income',  'code' => 'IRA',  'sort_order' => 1,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2,  'parent_id' => null, 'name' => 'Local Collections',                 'type' => 'income',  'code' => 'LC',   'sort_order' => 2,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3,  'parent_id' => null, 'name' => 'Grants & Subsidies',                'type' => 'income',  'code' => 'GS',   'sort_order' => 3,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],

            // Local Collections sub-categories
            ['id' => 4,  'parent_id' => 2,    'name' => 'Tax Collections',                   'type' => 'income',  'code' => 'LC-TX','sort_order' => 1,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5,  'parent_id' => 2,    'name' => 'Fees & Charges',                    'type' => 'income',  'code' => 'LC-FC','sort_order' => 2,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6,  'parent_id' => 2,    'name' => 'Permits & Clearances',              'type' => 'income',  'code' => 'LC-PC','sort_order' => 3,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7,  'parent_id' => 2,    'name' => 'Other Local Collections',           'type' => 'income',  'code' => 'LC-OT','sort_order' => 4,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],

            // ── Expense parent categories ──
            ['id' => 8,  'parent_id' => null, 'name' => 'Personnel Services (PS)',           'type' => 'expense', 'code' => 'PS',   'sort_order' => 1,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9,  'parent_id' => null, 'name' => 'Maintenance & Other Operating (MOOE)', 'type' => 'expense', 'code' => 'MOOE','sort_order' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 10, 'parent_id' => null, 'name' => 'Capital Outlay (CO)',               'type' => 'expense', 'code' => 'CO',   'sort_order' => 3,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],

            // Personnel Services sub-categories
            ['id' => 11, 'parent_id' => 8,    'name' => 'Salaries & Wages',                  'type' => 'expense', 'code' => 'PS-SW','sort_order' => 1,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 12, 'parent_id' => 8,    'name' => 'PERA & Allowances',                 'type' => 'expense', 'code' => 'PS-PA','sort_order' => 2,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 13, 'parent_id' => 8,    'name' => 'Honoraria',                         'type' => 'expense', 'code' => 'PS-HO','sort_order' => 3,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],

            // MOOE sub-categories
            ['id' => 14, 'parent_id' => 9,    'name' => 'Office Supplies',                   'type' => 'expense', 'code' => 'MOOE-OS','sort_order' => 1,'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 15, 'parent_id' => 9,    'name' => 'Utilities (Water & Light)',         'type' => 'expense', 'code' => 'MOOE-UT','sort_order' => 2,'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 16, 'parent_id' => 9,    'name' => 'Communication',                     'type' => 'expense', 'code' => 'MOOE-CM','sort_order' => 3,'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 17, 'parent_id' => 9,    'name' => 'Travelling Expenses',               'type' => 'expense', 'code' => 'MOOE-TR','sort_order' => 4,'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 18, 'parent_id' => 9,    'name' => 'Repairs & Maintenance',             'type' => 'expense', 'code' => 'MOOE-RM','sort_order' => 5,'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 19, 'parent_id' => 9,    'name' => 'Programs & Activities',             'type' => 'expense', 'code' => 'MOOE-PA','sort_order' => 6,'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 20, 'parent_id' => 9,    'name' => 'Other MOOE',                        'type' => 'expense', 'code' => 'MOOE-OT','sort_order' => 7,'is_active' => true, 'created_at' => $now, 'updated_at' => $now],

            // Capital Outlay sub-categories
            ['id' => 21, 'parent_id' => 10,   'name' => 'Infrastructure Projects',           'type' => 'expense', 'code' => 'CO-IP','sort_order' => 1,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 22, 'parent_id' => 10,   'name' => 'Equipment & Furniture',             'type' => 'expense', 'code' => 'CO-EF','sort_order' => 2,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 23, 'parent_id' => 10,   'name' => 'Other Capital Outlay',              'type' => 'expense', 'code' => 'CO-OT','sort_order' => 3,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_budget_categories');
    }
};
