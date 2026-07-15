<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_citizen_id_templates', function (Blueprint $table) {
            // Shared CSS and JS that apply to both front and back
            $table->longText('css_shared')->nullable()->after('css_back');
            $table->longText('js_shared')->nullable()->after('css_shared');
        });
    }

    public function down(): void
    {
        Schema::table('eb_citizen_id_templates', function (Blueprint $table) {
            $table->dropColumn(['css_shared', 'js_shared']);
        });
    }
};
