<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Walk-ins: people who need a document but are not registered residents.
     * They get a minimal eb_citizen record flagged is_walkin — hidden from
     * everything except document requests (see Citizen's "residents" scope) —
     * so holds, request history and the certificate placeholders keep working.
     * Each document type can allow or refuse walk-ins.
     */
    public function up(): void
    {
        Schema::table('eb_citizen', function (Blueprint $table) {
            $table->boolean('is_walkin')->default(false)->index();
            $table->string('walkin_id_type', 60)->nullable();     // ID presented, e.g. "Driver's License"
            $table->string('walkin_id_number', 60)->nullable();
            $table->dateTime('walkin_consent_at')->nullable();    // Data Privacy Act consent
        });

        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->boolean('allow_walkin')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('eb_citizen', function (Blueprint $table) {
            $table->dropColumn(['is_walkin', 'walkin_id_type', 'walkin_id_number', 'walkin_consent_at']);
        });
        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->dropColumn('allow_walkin');
        });
    }
};
