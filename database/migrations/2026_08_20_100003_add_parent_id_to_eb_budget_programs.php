<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * BBPF02 nests two levels deep: "2. BASIC SERVICES AND FACILITIES
     * PROGRAM" contains sub-programs A–E, and "3. SPECIAL PURPOSE
     * APPROPRIATION" contains A–M. Each sub-program carries its own
     * PS/MOOE/FE/CO breakdown, so line items hang off the child.
     */
    public function up(): void
    {
        Schema::table('eb_budget_programs', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('eb_budget_programs')
                ->cascadeOnDelete();

            // Section letter on the printed form ("A", "B", …) for children,
            // section number ("1", "2", "3") for top-level programs.
            $table->string('section_label', 8)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('eb_budget_programs', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'section_label']);
        });
    }
};
