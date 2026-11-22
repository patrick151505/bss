<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Login History (Settings → Login History): every successful login, wrong
 * password, lockout, blocked (disabled) account and log-out.
 *
 * Permission login_logs.view goes to the Super Admin and to every role that
 * can already see Activity Logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eb_login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->nullable();          // what was typed (kept for unknown emails)
            $table->string('event', 20);                  // login | failed | locked | disabled | logout
            $table->string('note')->nullable();           // e.g. "Wrong password", "Remember me"
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event', 'created_at']);
            $table->index('created_at');
            $table->index('email');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $perm = Permission::firstOrCreate(['name' => 'login_logs.view', 'guard_name' => 'web']);
        Role::where('name', 'Super Admin')->first()?->givePermissionTo($perm);
        Role::permission('activity_logs.view')->get()->each(fn ($r) => $r->givePermissionTo($perm));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('eb_login_logs');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::where('name', 'login_logs.view')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
