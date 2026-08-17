<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The core citizens table. Legacy base table (originally outside migrations);
 * recreated here so a fresh SaaS-tenant database builds from `php artisan migrate`.
 *
 * NOTE: the primary key is a signed int (not bigIncrements) to match the legacy
 * schema — several later tables reference it with `integer('citizen_id')`, so the
 * FK column types must line up or the constraint fails to form.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('eb_citizen')) {
            return;
        }

        Schema::create('eb_citizen', function (Blueprint $table) {
            // Signed int auto-increment to match the legacy int(11) schema — the
            // FK columns in later tables use signed integer('citizen_id'), so the
            // pk must also be signed or the constraints won't form.
            $table->integer('id', true);                    // (autoIncrement = true), signed
            $table->text('qrcode');
            $table->string('fname', 100);
            $table->text('mname')->nullable();
            $table->string('lname', 100);
            $table->string('suffix', 250)->default('')->nullable();
            $table->date('bday');
            $table->text('bplace');
            $table->text('contact')->nullable();
            $table->string('email', 250)->nullable();
            $table->tinyInteger('gender')->default(1);
            $table->integer('civil_status');
            $table->integer('is_soloparents')->nullable();
            $table->integer('address')->nullable();
            $table->string('no', 50)->nullable();
            $table->string('street', 250)->nullable();
            $table->integer('blk')->nullable();
            $table->integer('lot')->nullable();
            $table->integer('phase')->nullable();
            $table->date('year_stay')->nullable();
            $table->dateTime('date_created');
            $table->dateTime('date_approved')->nullable();
            $table->integer('approval_status')->default(2);
            $table->dateTime('date_last_updated');
            $table->integer('user_id_approved')->nullable();
            $table->integer('is_active')->default(1);
            $table->tinyInteger('owner_status');
            $table->text('complete_address')->nullable();
            $table->tinyInteger('voters')->default(0);
            $table->tinyInteger('is_notify')->default(0)->nullable();
            $table->tinyInteger('is_id_release')->default(0)->nullable();
            $table->string('pricinct_no', 100)->nullable();   // note: legacy typo kept
            $table->text('note')->nullable();
            $table->integer('is_download')->default(0);
            $table->integer('is_verify')->default(0)->nullable();
            $table->text('ic_email')->nullable();
            $table->text('ic_fullname')->nullable();
            $table->text('ic_contact')->nullable();
            $table->text('ic_address')->nullable();
            $table->text('ic_relationship')->nullable();
            $table->integer('is_pwd');
            $table->string('citizenship', 250)->nullable();
            $table->string('occupation', 250)->nullable();
            $table->string('profile', 250)->nullable();
            // NOTE: familyId / isHead / relationId are added later by the
            // household migration (2026_06_15_221415) — don't create them here.
            // The search indexes (qrcode / is_active / lname+fname) are added by
            // 2026_07_21_000003_add_search_indexes_to_eb_citizen — not here.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_citizen');
    }
};
