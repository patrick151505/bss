<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_fund_clusters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('eb_fund_clusters')->insert([
            ['name' => 'General Fund',              'code' => 'general_fund', 'sort_order' => 1,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Trust Fund',                'code' => 'trust_fund',   'sort_order' => 2,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'DRRMF',                     'code' => 'drrmf',        'sort_order' => 3,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Special Education Fund (SEF)', 'code' => 'sef',       'sort_order' => 4,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'SK Fund',                   'code' => 'sk_fund',      'sort_order' => 5,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gender and Development (GAD)', 'code' => 'gad',       'sort_order' => 6,  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_fund_clusters');
    }
};
