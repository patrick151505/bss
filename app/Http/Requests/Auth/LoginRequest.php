<?php

namespace App\Http\Requests\Auth;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Wrong passwords allowed for one email + IP before it is locked. */
    private const MAX_ATTEMPTS = 5;

    /** Window (seconds) in which those wrong passwords are counted. */
    private const ATTEMPT_WINDOW = 900;

    /**
     * Lock length (seconds) for the 1st, 2nd, 3rd and 4th-or-later lockout.
     * Each lockout lasts longer, so slow guessing gains almost nothing.
     */
    private const LOCK_STEPS = [60, 300, 900, 3600];

    /** How long (seconds) the lockout count is remembered after the last lockout. */
    private const LEVEL_MEMORY = 86400;

    /** Wrong passwords allowed from one IP, across all emails, per minute. */
    private const IP_MAX_ATTEMPTS = 20;

    private const IP_WINDOW = 60;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        // A deactivated account (Settings → Users) may not sign in, even with
        // the right password. Checked first so it never counts as a wrong password.
        $account = User::where('email', $this->input('email'))->first();
        if ($account && ! $account->is_active
            && Auth::getProvider()->validateCredentials($account, $this->only('password'))) {
            LoginLog::record('disabled', $account, null, 'Account is deactivated');

            throw ValidationException::withMessages([
                'email' => 'This account is deactivated. Please contact the barangay administrator.',
            ]);
        }

        if (! Auth::attempt($this->only('email', 'password'), $this->filled('remember'))) {
            $this->recordFailure();

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // A successful login wipes this account's record from this IP. The IP's
        // own counter is left alone: one correct password must not reset the
        // allowance of someone guessing other accounts from the same address.
        RateLimiter::clear($this->throttleKey());
        Cache::forget($this->lockKey());
        Cache::forget($this->levelKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited()
    {
        $lockedUntil = (int) Cache::get($this->lockKey(), 0);
        if ($lockedUntil > time()) {
            $this->throwThrottled($lockedUntil - time());
        }

        if (RateLimiter::tooManyAttempts($this->ipKey(), self::IP_MAX_ATTEMPTS)) {
            event(new Lockout($this));
            $this->throwThrottled(RateLimiter::availableIn($this->ipKey()));
        }
    }

    /**
     * Count a wrong password. On the 5th within the window, lock this email + IP
     * for the next step of the lock ladder.
     */
    private function recordFailure(): void
    {
        RateLimiter::hit($this->ipKey(), self::IP_WINDOW);
        RateLimiter::hit($this->throttleKey(), self::ATTEMPT_WINDOW);

        if (RateLimiter::attempts($this->throttleKey()) < self::MAX_ATTEMPTS) {
            return;
        }

        $level = (int) Cache::get($this->levelKey(), 0);
        $seconds = self::LOCK_STEPS[min($level, count(self::LOCK_STEPS) - 1)];

        Cache::put($this->levelKey(), $level + 1, self::LEVEL_MEMORY);
        Cache::put($this->lockKey(), time() + $seconds, $seconds);
        RateLimiter::clear($this->throttleKey());

        event(new Lockout($this));
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    private function throwThrottled(int $seconds): void
    {
        $seconds = max(1, $seconds);

        $wait = $seconds < 60
            ? $seconds . ' ' . Str::plural('second', $seconds)
            : (int) ceil($seconds / 60) . ' ' . Str::plural('minute', (int) ceil($seconds / 60));

        throw ValidationException::withMessages([
            'email' => "Too many login attempts. Please try again in {$wait}.",
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request (email + IP).
     */
    public function throttleKey()
    {
        return Str::lower($this->input('email')) . '|' . $this->ip();
    }

    private function ipKey(): string
    {
        return 'login-ip|' . $this->ip();
    }

    private function lockKey(): string
    {
        return 'login-lock|' . $this->throttleKey();
    }

    private function levelKey(): string
    {
        return 'login-level|' . $this->throttleKey();
    }
}
