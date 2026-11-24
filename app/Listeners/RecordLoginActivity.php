<?php

namespace App\Listeners;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Feeds Settings → Login History from Laravel's own auth events.
 * (Blocked/disabled accounts are recorded by LoginRequest directly.)
 */
class RecordLoginActivity
{
    public function login(Login $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;
        LoginLog::record('login', $user, null, $event->remember ? 'Remember me' : null);
    }

    public function failed(Failed $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;
        LoginLog::record('failed', $user, $event->credentials['email'] ?? null,
            $user ? 'Wrong password' : 'No account with this email');
    }

    public function lockout(Lockout $event): void
    {
        $email = (string) $event->request->input('email');
        $user  = $email !== '' ? User::where('email', $email)->first() : null;
        LoginLog::record('locked', $user, $email, 'Too many wrong passwords');
    }

    public function logout(Logout|CurrentDeviceLogout $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;
        if ($user) {
            LoginLog::record('logout', $user);
        }
    }

    public function subscribe($events): array
    {
        return [
            Login::class   => 'login',
            Failed::class  => 'failed',
            Lockout::class => 'lockout',
            Logout::class  => 'logout',
            CurrentDeviceLogout::class => 'logout',   // the Logout button (this device only)
        ];
    }
}
