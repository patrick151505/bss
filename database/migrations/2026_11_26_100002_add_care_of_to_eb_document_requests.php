<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Walk-in document requests must name the resident they are "care of"
 * (who vouches for them). NULL for residents' own requests.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('eb_document_requests', 'care_of_citizen_id')) {
            return;
        }
        Schema::table('eb_document_requests', function (Blueprint $table) {
            // eb_citizen.id is int(11), so no FK type mismatch: plain indexed int.
            $table->integer('care_of_citizen_id')->nullable()->after('citizen_id')->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('eb_document_requests', 'care_of_citizen_id')) {
            Schema::table('eb_document_requests', function (Blueprint $table) {
                $table->dropIndex(['care_of_citizen_id']);
                $table->dropColumn('care_of_citizen_id');
            });
        }
    }
};
