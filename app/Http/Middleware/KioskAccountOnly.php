<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * An account with the "Kiosk" role is logged in on a public tablet, so it may
 * only use the kiosk (and the registration form it opens) — never the staff
 * pages, even ones every logged-in user can see.
 */
class KioskAccountOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->hasRole('Kiosk') && !$user->hasRole('Super Admin')
            && !$request->routeIs('kiosk.*', 'register.citizen*', 'login', 'logout')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'This account can only use the kiosk.'], 403)
                : redirect()->route('kiosk.home');
        }

        return $next($request);
    }
}
