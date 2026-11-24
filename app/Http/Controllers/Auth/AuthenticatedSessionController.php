<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /** Cookie holding the email of the last "Remember me" login on this device. */
    public const EMAIL_COOKIE = 'remembered_email';

    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        
        $request->authenticate();

        $request->session()->regenerate();

        // "Remember me" also keeps the email filled in (and the box ticked) on
        // this device's login page after logging out; unticking forgets it.
        if ($request->filled('remember')) {
            \Illuminate\Support\Facades\Cookie::queue(self::EMAIL_COOKIE, $request->input('email'), 60 * 24 * 365);
        } else {
            \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::forget(self::EMAIL_COOKIE));
        }

        // A configured landing page is an explicit preference — honor it directly
        // rather than via intended(), which would otherwise prioritise whatever URL
        // the user was bounced to login from and ignore their setting.
        if ($landing = $request->user()->resolveLandingRoute()) {
            return redirect()->route($landing);
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        // Log out THIS device only. logout() would reset the account's remember
        // token and sign out every other device that ticked "Remember me".
        Auth::guard('web')->logoutCurrentDevice();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out.');
    }
}