<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Blotter;
use App\Models\Citizen;
use App\Models\DocumentRequest;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ActivityDashboardController extends Controller
{
    public function index()
    {
        $today     = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart= Carbon::now()->startOfMonth();

        // ── KPI Cards ────────────────────────────────────────────────
        $kpi = [
            'citizens_today'    => Citizen::whereDate('date_created', $today)->count(),
            'citizens_month'    => Citizen::whereDate('date_created', '>=', $monthStart)->count(),
            'docs_today'        => DocumentRequest::whereDate('created_at', $today)->count(),
            'docs_pending'      => DocumentRequest::where('status', 'pending')->count(),
            'blotters_week'     => Blotter::whereDate('filed_date', '>=', $weekStart)->count(),
            'blotters_open'     => Blotter::whereIn('status', ['filed', 'ongoing'])->count(),
            'events_month'      => Event::whereYear('event_date', $today->year)
                                        ->whereMonth('event_date', $today->month)->count(),
            'events_upcoming'   => Event::where('event_date', '>=', $today)
                                        ->where('status', 'open')->count(),
            'total_citizens'    => Citizen::where('is_active', 1)->count(),
            'total_households'  => \App\Models\Household::count(),
        ];

        // Budget is left out of this dashboard: its log entries are hidden from the feed and charts.
        $noBudget = fn($q) => $q->where(fn($w) => $w->whereNull('subject_type')
            ->orWhereRaw("LOWER(subject_type) NOT LIKE 'budget%'"))
            ->whereRaw("LOWER(COALESCE(description, '')) NOT LIKE '%voucher%'");

        // ── Activity Feed (last 30) ───────────────────────────────────
        $feed = ActivityLog::with('user')->tap($noBudget)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $feedJson = $feed->map(function ($l) {
            return [
                'action'       => $l->action,
                'user'         => $l->user ? $l->user->name : 'System',
                'description'  => $l->description,
                'subject_type' => $l->subject_type,
                'time'         => $l->created_at ? $l->created_at->diffForHumans() : '',
            ];
        })->values();

        // ── Recent Document Requests (last 8) ────────────────────────
        $recentDocs = DocumentRequest::with(['citizen', 'documentType'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        // ── Recent Blotters (last 8) ─────────────────────────────────
        $recentBlotters = Blotter::orderByDesc('filed_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        // ── Weekly Activity Chart (last 7 days) ──────────────────────
        $weeklyLabels = [];
        $weeklyData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $weeklyLabels[] = $day->format('D M d');
            $weeklyData[]   = ActivityLog::tap($noBudget)->whereDate('created_at', $day)->count();
        }

        // ── Module Breakdown (donut) ──────────────────────────────────
        $moduleBreakdown = ActivityLog::select('subject_type', DB::raw('COUNT(*) as total'))->tap($noBudget)
            ->whereNotNull('subject_type')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('subject_type')
            ->orderByDesc('total')
            ->get();

        // ── Action Type Breakdown ─────────────────────────────────────
        $actionBreakdown = ActivityLog::select('action', DB::raw('COUNT(*) as total'))->tap($noBudget)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('action')
            ->orderByDesc('total')
            ->get();

        // ── Staff Activity ────────────────────────────────────────────
        $staffActivity = User::select('users.id', 'users.name', 'users.email', 'users.is_active')
            // Budget actions don't count here either.
            ->leftJoin('eb_activity_logs', fn($j) => $j->on('users.id', '=', 'eb_activity_logs.user_id')
                ->where(fn($w) => $w->whereNull('eb_activity_logs.subject_type')
                    ->orWhereRaw("LOWER(eb_activity_logs.subject_type) NOT LIKE 'budget%'"))
                ->whereRaw("LOWER(COALESCE(eb_activity_logs.description, '')) NOT LIKE '%voucher%'"))
            ->selectRaw('
                COUNT(eb_activity_logs.id) as total_actions,
                SUM(CASE WHEN DATE(eb_activity_logs.created_at) = CURDATE() THEN 1 ELSE 0 END) as today_actions,
                SUM(CASE WHEN eb_activity_logs.created_at >= ? THEN 1 ELSE 0 END) as week_actions,
                MAX(eb_activity_logs.created_at) as last_active
            ', [$weekStart])
            ->groupBy('users.id', 'users.name', 'users.email', 'users.is_active')
            ->orderByDesc('today_actions')
            ->get();

        // ── Today's timeline (hourly buckets) ────────────────────────
        $hourlyData = ActivityLog::select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as total')
            )
            ->tap($noBudget)
            ->whereDate('created_at', $today)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        $hourlyLabels = [];
        $hourlyValues = [];
        for ($h = 0; $h <= 23; $h++) {
            $hourlyLabels[] = Carbon::today()->setHour($h)->format('g A');
            $hourlyValues[] = $hourlyData->get($h)?->total ?? 0;
        }

        return view('dashboard.activity', compact(
            'kpi',
            'feed',
            'feedJson',
            'recentDocs',
            'recentBlotters',
            'weeklyLabels',
            'weeklyData',
            'moduleBreakdown',
            'actionBreakdown',
            'staffActivity',
            'hourlyLabels',
            'hourlyValues',
        ));
    }
}
