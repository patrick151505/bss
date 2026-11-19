<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Legislation;
use Illuminate\Http\Request;
use App\Models\User;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['action', 'subject_type', 'subject_id', 'date_from', 'date_to', 'user_id']);

        $query = ActivityLog::with('user')
            ->when($filters['action'] ?? null, fn($q, $v) => $q->where('action', $v))
            ->when($filters['subject_type'] ?? null, fn($q, $v) => $q->where('subject_type', $v))
            // One record's history (e.g. "View all" from an ordinance page); only with a module.
            ->when(($filters['subject_type'] ?? null) && ($filters['subject_id'] ?? null), fn($q) => $q->where('subject_id', (int) $filters['subject_id']))
            ->when($filters['user_id'] ?? null, fn($q, $v) => $q->where('user_id', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('created_at');

        $logs  = $query->paginate(25)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);

        // Ordinances / resolutions on this page that still exist, so their entries can link to them.
        $legislationIds = $logs->getCollection()->where('subject_type', 'legislation')->pluck('subject_id')->filter()->unique();
        $legislations   = $legislationIds->isEmpty()
            ? collect()
            : Legislation::whereIn('id', $legislationIds)->get(['id', 'type', 'number'])->keyBy('id');

        // Heading when filtered to one record.
        $subjectLabel = ($filters['subject_type'] ?? null) === 'legislation' && ($filters['subject_id'] ?? null)
            ? Legislation::withTrashed()->find((int) $filters['subject_id'])?->reference()
            : null;

        return view('activity_logs.index', compact('logs', 'filters', 'users', 'legislations', 'subjectLabel'));
    }
}
