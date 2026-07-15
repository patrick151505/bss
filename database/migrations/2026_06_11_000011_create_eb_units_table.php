<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $defaults = [
            'pcs', 'sets', 'lot/s', 'unit/s', 'box/es', 'reams', 'rolls',
            'sheets', 'packs', 'bags', 'bottles', 'pairs',
            'm', 'L', 'kg', 'hrs', 'days', 'months',
        ];

        foreach ($defaults as $i => $name) {
            DB::table('eb_units')->insert([
                'name'       => $name,
                'sort_order' => $i + 1,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_units');
    }
};
