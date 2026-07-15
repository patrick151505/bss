<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->string('snap_paper_bg')->nullable()->after('remarks');
            $table->unsignedSmallInteger('snap_padding_top')->default(48)->after('snap_paper_bg');
            $table->unsignedSmallInteger('snap_padding_bottom')->default(72)->after('snap_padding_top');
            $table->unsignedSmallInteger('snap_padding_left')->default(72)->after('snap_padding_bottom');
            $table->unsignedSmallInteger('snap_padding_right')->default(72)->after('snap_padding_left');
        });
    }

    public function down(): void
    {
        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->dropColumn([
                'snap_paper_bg',
                'snap_padding_top',
                'snap_padding_bottom',
                'snap_padding_left',
                'snap_padding_right',
            ]);
        });
    }
};
