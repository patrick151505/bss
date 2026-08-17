<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Amount the citizen actually tendered when a paid document was released.
        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->decimal('amount_paid', 10, 2)->nullable()->after('fee_paid');
        });
    }

    public function down(): void
    {
        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->dropColumn('amount_paid');
        });
    }
};
