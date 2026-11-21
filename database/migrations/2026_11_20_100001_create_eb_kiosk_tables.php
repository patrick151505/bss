<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Self-service kiosk at the barangay hall: paired devices, the pictures shown on
 * its start screen, which documents it offers, and requests that came from it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_kiosks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('secret', 64)->nullable();           // sha256 of the device cookie secret
            $table->string('pair_code', 64)->nullable()->unique(); // one-time pairing link code
            $table->timestamp('pair_expires_at')->nullable();
            $table->timestamp('paired_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedBigInteger('registration_token_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('eb_kiosk_slides', function (Blueprint $table) {
            $table->id();
            $table->string('image');
            $table->string('caption', 200)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->boolean('allow_kiosk')->default(false)->after('allow_walkin');
        });

        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->string('source', 20)->default('counter')->after('created_by')->index();
            $table->unsignedBigInteger('kiosk_id')->nullable()->after('source');
            $table->string('queue_no', 10)->nullable()->after('kiosk_id');
        });
    }

    public function down(): void
    {
        Schema::table('eb_document_requests', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'kiosk_id', 'queue_no']);
        });
        Schema::table('eb_document_types', function (Blueprint $table) {
            $table->dropColumn('allow_kiosk');
        });
        Schema::dropIfExists('eb_kiosk_slides');
        Schema::dropIfExists('eb_kiosks');
    }
};
