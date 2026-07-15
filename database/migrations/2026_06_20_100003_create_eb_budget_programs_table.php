<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('eb_budget_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            // special_fund links this program to a mandatory fund (auto-computed ceiling)
            // null = regular discretionary program
            $table->enum('special_fund', ['dev_fund', 'sk_fund', 'calamity', 'gad'])->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default programs — names only, no amounts
        $now = now();
        DB::table('eb_budget_programs')->insert([
            ['name' => 'General Government Services',  'special_fund' => null,       'sort_order' => 1,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Health & Nutrition Services',  'special_fund' => null,       'sort_order' => 2,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Peace & Order',                'special_fund' => null,       'sort_order' => 3,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Social Services',              'special_fund' => null,       'sort_order' => 4,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Development Fund (20%)',       'special_fund' => 'dev_fund', 'sort_order' => 5,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'SK Fund (10%)',                'special_fund' => 'sk_fund',  'sort_order' => 6,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Calamity / LDRRM Fund (5%)',  'special_fund' => 'calamity', 'sort_order' => 7,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'GAD Fund (5%)',                'special_fund' => 'gad',      'sort_order' => 8,  'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_budget_programs');
    }
};
