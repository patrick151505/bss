<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_fiscal_years', function (Blueprint $table) {
            $table->enum('phase', [
                'preparation',
                'authorization',
                'review',
                'execution',
                'accountability',
            ])->default('preparation')->after('is_active');

            $table->timestamp('phase_updated_at')->nullable()->after('phase');
            $table->foreignId('phase_updated_by')->nullable()->after('phase_updated_at')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('eb_fiscal_years', function (Blueprint $table) {
            $table->dropForeign(['phase_updated_by']);
            $table->dropColumn(['phase', 'phase_updated_at', 'phase_updated_by']);
        });
    }
};
