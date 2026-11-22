<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry in Settings → Login History.
 */
class LoginLog extends Model
{
    protected $table = 'eb_login_logs';

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'email', 'event', 'note', 'ip', 'user_agent', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    /** event => [label, pill classes, dot class] */
    public const EVENTS = [
        'login'    => ['Logged in',       'bg-green-100 text-green-800',   'bg-green-400'],
        'logout'   => ['Logged out',      'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200', 'bg-gray-400'],
        'failed'   => ['Failed',          'bg-yellow-100 text-yellow-800', 'bg-yellow-400'],
        'locked'   => ['Locked out',      'bg-red-100 text-red-800',       'bg-red-400'],
        'disabled' => ['Blocked account', 'bg-red-100 text-red-800',       'bg-red-400'],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Write one entry for the current request. Never breaks the login itself. */
    public static function record(string $event, ?User $user = null, ?string $email = null, ?string $note = null): void
    {
        try {
            $request = request();
            static::create([
                'user_id'    => $user?->id,
                'email'      => mb_substr((string) ($email ?? $user?->email), 0, 255) ?: null,
                'event'      => $event,
                'note'       => $note ? mb_substr($note, 0, 255) : null,
                'ip'         => $request?->ip(),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 500) : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** "Chrome on Windows", "Safari on iPhone" … from the browser's user agent. */
    public function device(): string
    {
        $ua = (string) $this->user_agent;
        if ($ua === '') {
            return 'Unknown device';
        }

        $browser = match (true) {
            str_contains($ua, 'Edg/')                                   => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera')     => 'Opera',
            str_contains($ua, 'SamsungBrowser')                         => 'Samsung Internet',
            str_contains($ua, 'Firefox/') || str_contains($ua, 'FxiOS') => 'Firefox',
            str_contains($ua, 'Chrome/') || str_contains($ua, 'CriOS') => 'Chrome',
            str_contains($ua, 'Safari/')                                => 'Safari',
            default                                                     => 'Browser',
        };

        $os = match (true) {
            str_contains($ua, 'iPhone')                                   => 'iPhone',
            str_contains($ua, 'iPad')                                     => 'iPad',
            str_contains($ua, 'Android')                                  => 'Android',
            str_contains($ua, 'Windows')                                  => 'Windows',
            str_contains($ua, 'Mac OS X') || str_contains($ua, 'Macintosh') => 'Mac',
            str_contains($ua, 'CrOS')                                     => 'Chromebook',
            str_contains($ua, 'Linux')                                    => 'Linux',
            default                                                       => null,
        };

        return $os ? "{$browser} on {$os}" : $browser;
    }

    public function isMobile(): bool
    {
        return (bool) preg_match('/Mobile|Android|iPhone|iPad/i', (string) $this->user_agent);
    }
}
