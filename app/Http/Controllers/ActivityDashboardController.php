<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Blotter;
use App\Models\Citizen;
use App\Models\DocumentRequest;
use App\Models\BudgetTransaction;
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
            'total_households'  => DB::table('eb_household')->count(),
            'budget_paid'       => BudgetTransaction::where('status', 'paid')->sum('amount'),
            'budget_pending'    => BudgetTransaction::whereIn('status', ['draft','approved'])->count(),
        ];

        // ── Activity Feed (last 30) ───────────────────────────────────
        $feed = ActivityLog::with('user')
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

        // ── Recent Budget Vouchers (last 8) ──────────────────────────
        $recentVouchers = BudgetTransaction::with('fiscalYear')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        // ── Weekly Activity Chart (last 7 days) ──────────────────────
        $weeklyLabels = [];
        $weeklyData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $weeklyLabels[] = $day->format('D M d');
            $weeklyData[]   = ActivityLog::whereDate('created_at', $day)->count();
        }

        // ── Module Breakdown (donut) ──────────────────────────────────
        $moduleBreakdown = ActivityLog::select('subject_type', DB::raw('COUNT(*) as total'))
            ->whereNotNull('subject_type')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('subject_type')
            ->orderByDesc('total')
            ->get();

        // ── Action Type Breakdown ─────────────────────────────────────
        $actionBreakdown = ActivityLog::select('action', DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('action')
            ->orderByDesc('total')
            ->get();

        // ── Staff Activity ────────────────────────────────────────────
        $staffActivity = User::select('users.id', 'users.name', 'users.email', 'users.is_active')
            ->leftJoin('eb_activity_logs', 'users.id', '=', 'eb_activity_logs.user_id')
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

        // ── Shared status→color map ───────────────────────────────────
        $voucherStatusColors = [
            'paid'      => '#0acf97',
            'approved'  => '#727cf5',
            'draft'     => '#ffbc00',
            'cancelled' => '#fa5c7c',
        ];

        // ── Voucher Bar Chart (top 10 by amount) ─────────────────────
        $voucherBar = BudgetTransaction::orderByDesc('amount')
            ->limit(10)
            ->get(['voucher_no', 'payee', 'amount', 'status']);

        $voucherBarLabels  = $voucherBar->map(function ($v) {
            return $v->voucher_no . ' — ' . \Illuminate\Support\Str::limit($v->payee, 20);
        })->values()->toArray();

        $voucherBarAmounts = $voucherBar->pluck('amount')->map(fn($a) => (float) $a)->values()->toArray();

        $voucherBarColors  = $voucherBar->map(function ($v) use ($voucherStatusColors) {
            return $voucherStatusColors[$v->status] ?? '#6b7280';
        })->values()->toArray();

        // ── Voucher Donut Chart (total amount by status) ──────────────
        $voucherDonut = BudgetTransaction::select('status', DB::raw('SUM(amount) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $voucherDonutLabels  = $voucherDonut->pluck('status')->map(fn($s) => ucfirst($s))->values()->toArray();
        $voucherDonutAmounts = $voucherDonut->pluck('total')->map(fn($a) => (float) $a)->values()->toArray();
        $voucherDonutColors  = $voucherDonut->map(function ($row) use ($voucherStatusColors) {
            return $voucherStatusColors[$row->status] ?? '#6b7280';
        })->values()->toArray();

        return view('dashboard.activity', compact(
            'kpi',
            'feed',
            'feedJson',
            'recentDocs',
            'recentBlotters',
            'recentVouchers',
            'weeklyLabels',
            'weeklyData',
            'moduleBreakdown',
            'actionBreakdown',
            'staffActivity',
            'hourlyLabels',
            'hourlyValues',
            'voucherBarLabels',
            'voucherBarAmounts',
            'voucherBarColors',
            'voucherDonut',
            'voucherDonutLabels',
            'voucherDonutAmounts',
            'voucherDonutColors',
        ));
    }
}
