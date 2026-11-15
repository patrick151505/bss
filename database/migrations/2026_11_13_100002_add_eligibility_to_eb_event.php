<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Event eligibility rules on top of allowed areas: age range, gender, groups
     * (senior / PWD / solo parent / voter) and citizen tags. NULL = no extra rules.
     * Shape: {"min_age":60,"max_age":null,"gender":null,"groups":["senior","pwd"],
     *         "groups_match":"any","tags":[3,5]}
     */
    public function up(): void
    {
        Schema::table('eb_event', function (Blueprint $table) {
            $table->json('eligibility')->nullable()->after('allowed_addresses');
        });
    }

    public function down(): void
    {
        Schema::table('eb_event', function (Blueprint $table) {
            $table->dropColumn('eligibility');
        });
    }
};
