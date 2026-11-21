<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // The kiosk screen stays open for days, longer than a session lasts, so its
        // CSRF token goes stale. These routes still need a logged-in kiosk.use
        // account, and the session/remember cookies are SameSite=Lax, so another
        // site's POST arrives without them and is refused.
        'kiosk/scan',
        'kiosk/preview',
        'kiosk/submit',
        'kiosk/update',
        'kiosk/join',
    ];
}
