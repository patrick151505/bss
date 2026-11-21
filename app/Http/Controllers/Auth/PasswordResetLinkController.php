<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Same answer whether or not the email has an account, so this page can't
        // be used to find out who has one. Asking again within a minute is ignored.
        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'The email could not be sent right now. Please ask your administrator to reset your password.']);
        }

        if ($status === Password::RESET_LINK_SENT || $status === Password::INVALID_USER) {
            return back()->with('status', 'If that email has an account, a link to set a new password is on its way. Check your inbox (and spam folder). The link works for 60 minutes.');
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => 'A link was sent a moment ago. Please wait a minute before asking again.']);
    }
}
