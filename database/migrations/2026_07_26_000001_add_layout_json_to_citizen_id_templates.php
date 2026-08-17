<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_citizen_id_templates', function (Blueprint $table) {
            // Editable drag-and-drop layout (list of positioned elements) per side.
            // The compiled html_front/html_back/css_shared are still what print uses;
            // this column only holds the visual-editor source of truth.
            $table->json('layout_front')->nullable()->after('html_front');
            $table->json('layout_back')->nullable()->after('html_back');
        });
    }

    public function down(): void
    {
        Schema::table('eb_citizen_id_templates', function (Blueprint $table) {
            $table->dropColumn(['layout_front', 'layout_back']);
        });
    }
};
