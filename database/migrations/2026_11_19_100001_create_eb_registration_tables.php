<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online citizen self-registration.
 *  - eb_registration_tokens: links the Super Admin creates (Settings → Registration Links).
 *  - eb_citizen_registrations: what people submit through a link. Kept apart from
 *    eb_citizen until staff approve it, so unreviewed entries never show up in
 *    citizen lists, event check-in or household pickers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_registration_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('label', 150);
            $table->dateTime('expires_at')->nullable();
            $table->unsignedInteger('max_uses')->nullable();     // null = no limit
            $table->unsignedInteger('uses')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('eb_citizen_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('token_id')->nullable()->index();
            $table->string('status', 20)->default('pending')->index();   // pending | approved | rejected
            $table->string('fname', 250);
            $table->string('mname', 250)->nullable();
            $table->string('lname', 250);
            $table->string('suffix', 50)->nullable();
            $table->date('bday');
            $table->json('data');                                        // every other submitted field
            $table->string('photo')->nullable();
            $table->unsignedBigInteger('citizen_id')->nullable();        // set when approved
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('reject_reason', 500)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_citizen_registrations');
        Schema::dropIfExists('eb_registration_tokens');
    }
};
