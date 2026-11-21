<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A hold can be placed straight from a blotter case. The case is kept on
     * the hold so the citizen's hold history can point back to it. Nullable:
     * holds placed from the citizen page have no case.
     */
    public function up(): void
    {
        Schema::table('eb_citizen_holds', function (Blueprint $table) {
            $table->unsignedBigInteger('blotter_id')->nullable()->after('reason');
            $table->index('blotter_id');
        });
    }

    public function down(): void
    {
        Schema::table('eb_citizen_holds', function (Blueprint $table) {
            $table->dropIndex(['blotter_id']);
            $table->dropColumn('blotter_id');
        });
    }
};
