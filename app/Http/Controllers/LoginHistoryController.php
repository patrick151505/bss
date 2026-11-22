<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Settings → Login History: who signed in, from where, and failed attempts.
 */
class LoginHistoryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['q', 'event', 'user_id', 'ip', 'date_from', 'date_to']);

        $logs = LoginLog::with('user:id,name,email')
            ->when($filters['event'] ?? null, fn ($q, $v) => $q->where('event', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', (int) $v))
            ->when($filters['ip'] ?? null, fn ($q, $v) => $q->where('ip', $v))
            ->when($filters['q'] ?? null, function ($q, $v) {
                $q->where(fn ($w) => $w->where('email', 'like', "%{$v}%")
                    ->orWhere('ip', 'like', "%{$v}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$v}%")));
            })
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        // Today at a glance.
        $today = LoginLog::whereDate('created_at', today())
            ->selectRaw('event, COUNT(*) c')->groupBy('event')->pluck('c', 'event');

        $stats = [
            'login'   => (int) ($today['login'] ?? 0),
            'failed'  => (int) ($today['failed'] ?? 0),
            'blocked' => (int) ($today['locked'] ?? 0) + (int) ($today['disabled'] ?? 0),
            'users'   => LoginLog::whereDate('created_at', today())->where('event', 'login')
                            ->distinct('user_id')->count('user_id'),
        ];

        $users = User::orderBy('name')->get(['id', 'name']);

        return view('settings.login-history', compact('logs', 'filters', 'stats', 'users'));
    }
}
