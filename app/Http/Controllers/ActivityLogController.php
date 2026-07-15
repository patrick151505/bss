<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Models\User;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['action', 'subject_type', 'date_from', 'date_to', 'user_id']);

        $query = ActivityLog::with('user')
            ->when($filters['action'] ?? null, fn($q, $v) => $q->where('action', $v))
            ->when($filters['subject_type'] ?? null, fn($q, $v) => $q->where('subject_type', $v))
            ->when($filters['user_id'] ?? null, fn($q, $v) => $q->where('user_id', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('created_at');

        $logs  = $query->paginate(25)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('activity_logs.index', compact('logs', 'filters', 'users'));
    }
}
