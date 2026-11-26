<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settings → Backups: one row per backup run, plus the Google Drive
 * connection (encrypted) on eb_settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('eb_backups')) {
            Schema::create('eb_backups', function (Blueprint $table) {
                $table->id();
                $table->string('filename')->nullable();
                $table->unsignedBigInteger('size')->nullable();          // bytes
                $table->unsignedInteger('files_count')->nullable();      // uploads included
                $table->string('status', 20)->default('running');        // running | success | failed
                $table->string('drive_status', 20)->nullable();          // uploaded | failed | skipped
                $table->string('drive_file_id')->nullable();
                $table->text('message')->nullable();
                $table->unsignedBigInteger('triggered_by')->nullable();  // user id; null = nightly schedule
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamp('deleted_local_at')->nullable();       // removed by the 7-day clean-up
                $table->timestamps();
                $table->index(['status', 'started_at']);
            });
        }
        if (!Schema::hasColumn('eb_settings', 'backup_google')) {
            Schema::table('eb_settings', function (Blueprint $table) {
                $table->text('backup_google')->nullable();   // encrypted JSON: refresh token, account email, folder id
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_backups');
        if (Schema::hasColumn('eb_settings', 'backup_google')) {
            Schema::table('eb_settings', fn (Blueprint $table) => $table->dropColumn('backup_google'));
        }
    }
};
