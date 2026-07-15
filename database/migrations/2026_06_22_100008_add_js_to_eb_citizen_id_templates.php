<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_citizen_id_templates', function (Blueprint $table) {
            $table->longText('js_front')->nullable()->after('css_front');
            $table->longText('js_back')->nullable()->after('css_back');
        });
    }

    public function down(): void
    {
        Schema::table('eb_citizen_id_templates', function (Blueprint $table) {
            $table->dropColumn(['js_front', 'js_back']);
        });
    }
};
