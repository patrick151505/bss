<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\RegistrationToken;
use Illuminate\Http\Request;

/**
 * Settings → Registration Links. Super Admin only: each link lets the public
 * submit a citizen registration, which staff then approve or reject.
 */
class RegistrationTokenController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()?->hasRole('Super Admin'), 403, 'Only the Super Admin can manage registration links.');
            return $next($request);
        });
    }

    public function index()
    {
        $tokens = RegistrationToken::with('createdBy')
            ->withCount(['registrations as pending_count' => fn($q) => $q->where('status', 'pending')])
            ->latest()->get();

        return view('settings.registration-links', compact('tokens'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label'      => 'required|string|max:150',
            'expires_at' => 'nullable|date|after:now',
            'max_uses'   => 'nullable|integer|min:1|max:100000',
        ], [
            'expires_at.after' => 'The expiry must be in the future.',
        ]);

        $token = RegistrationToken::create([
            'token'      => RegistrationToken::newToken(),
            'label'      => $data['label'],
            'expires_at' => $data['expires_at'] ?? null,
            'max_uses'   => $data['max_uses'] ?? null,
            'is_active'  => true,
            'created_by' => auth()->id(),
        ]);

        ActivityLog::record('created', 'registration_link', $token->id, "Registration link created: {$token->label}");

        return redirect()->route('settings.registration-links')->with('success', 'Registration link created.');
    }

    public function toggle(RegistrationToken $token)
    {
        $token->update(['is_active' => !$token->is_active]);

        ActivityLog::record('updated', 'registration_link', $token->id,
            "Registration link {$token->label} " . ($token->is_active ? 'turned on' : 'turned off'));

        return back()->with('success', $token->is_active ? 'Link turned on.' : 'Link turned off — it no longer accepts registrations.');
    }

    public function destroy(RegistrationToken $token)
    {
        // Submissions stay (token_id becomes a dangling reference); only the link goes.
        $label = $token->label;
        $token->delete();

        ActivityLog::record('deleted', 'registration_link', $token->id, "Registration link deleted: {$label}");

        return back()->with('success', 'Registration link deleted.');
    }
}
