<?php

namespace App\Http\Controllers;

use App\Models\Citizen;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DocumentDashboardController extends Controller
{
    public function index(Request $request)
    {
        // ── Date range (today / week / month / custom). Default: this month.
        [$from, $to, $range] = $this->resolveRange($request);

        // Request activity is counted by request date (created_at)…
        $scoped = fn () => DocumentRequest::whereBetween('created_at', [$from, $to]);
        // …money by release date — that is when it is actually received —
        // using the same "collected" rule as the Requests page.
        $collected = fn () => DocumentRequest::collectable()->whereBetween('released_at', [$from, $to]);
        $sumCollected = 'COALESCE(SUM(' . DocumentRequest::COLLECTED_SQL . '), 0)';

        // ── KPI cards ──────────────────────────────────────────────────
        $byStatus = $scoped()->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
        $kpis = [
            'total'    => (int) $byStatus->sum(),
            'released' => (int) ($byStatus['released'] ?? 0),
            'voided'   => (int) ($byStatus['voided'] ?? 0),
            'pending'  => (int) ($byStatus['pending'] ?? 0)
                        + (int) ($byStatus['approved'] ?? 0)
                        + (int) ($byStatus['ready_for_release'] ?? 0),
            'prints'   => (int) $scoped()->sum('print_count'),
            'fees'     => (float) $collected()->selectRaw("$sumCollected AS v")->value('v'),
            'paid_count' => (int) $collected()->count(),
        ];

        // ── Status pie ─────────────────────────────────────────────────
        $statusChart = $byStatus->map(fn ($c, $s) => [
            'label' => ucfirst(str_replace('_', ' ', $s)),
            'value' => (int) $c,
        ])->values();

        // ── Requests over time (daily line) ────────────────────────────
        $daily = $scoped()
            ->selectRaw('DATE(created_at) d, COUNT(*) c')
            ->groupBy('d')->orderBy('d')->pluck('c', 'd');
        $timeline = [];
        for ($day = $from->copy(); $day <= $to; $day->addDay()) {
            $key = $day->format('Y-m-d');
            $timeline[] = ['date' => $day->format('M d'), 'count' => (int) ($daily[$key] ?? 0)];
        }

        // ── Fees collected over time (by release date) ─────────────────
        $dailyFees = $collected()
            ->selectRaw("DATE(released_at) d, $sumCollected amt")
            ->groupBy('d')->orderBy('d')->pluck('amt', 'd');
        $revenueTimeline = [];
        for ($day = $from->copy(); $day <= $to; $day->addDay()) {
            $key = $day->format('Y-m-d');
            $revenueTimeline[] = ['date' => $day->format('M d'), 'amount' => (float) ($dailyFees[$key] ?? 0)];
        }

        // ── Top document types (by request count) ──────────────────────
        $topTypes = $this->rankByType(
            $scoped()->selectRaw('document_type_id, COUNT(*) v')->groupBy('document_type_id')->orderByDesc('v')->limit(10)->get()
        );

        // ── Most printed document types ────────────────────────────────
        $topPrinted = $this->rankByType(
            $scoped()->selectRaw('document_type_id, SUM(print_count) v')->groupBy('document_type_id')
                ->havingRaw('SUM(print_count) > 0')->orderByDesc('v')->limit(10)->get()
        );

        // ── Highest-earning document types (paid) ──────────────────────
        $topEarning = $this->rankByType(
            $collected()->selectRaw("document_type_id, $sumCollected v")->groupBy('document_type_id')
                ->havingRaw('v > 0')->orderByDesc('v')->limit(10)->get()
        );

        // ── Top purposes ───────────────────────────────────────────────
        $topPurposes = $scoped()
            ->whereNotNull('purpose')->where('purpose', '!=', '')
            ->selectRaw('purpose, COUNT(*) v')->groupBy('purpose')->orderByDesc('v')->limit(10)
            ->get()->map(fn ($r) => ['label' => $r->purpose, 'value' => (int) $r->v]);

        // ── Top 10 citizens (by request count) ─────────────────────────
        $citizenRows = $scoped()->selectRaw('citizen_id, COUNT(*) v')
            ->groupBy('citizen_id')->orderByDesc('v')->limit(10)->get();
        $citizenNames = Citizen::whereIn('id', $citizenRows->pluck('citizen_id'))->get()
            ->keyBy('id')->map->full_name;
        $topCitizens = $citizenRows->map(fn ($r) => [
            'id'    => $r->citizen_id,
            'label' => $citizenNames[$r->citizen_id] ?? '—',
            'value' => (int) $r->v,
        ]);

        return view('documents.dashboard', compact(
            'kpis', 'statusChart', 'timeline', 'revenueTimeline', 'topTypes', 'topPrinted',
            'topEarning', 'topPurposes', 'topCitizens', 'range', 'from', 'to'
        ));
    }

    // Resolve a labeled document-type ranking collection into label/value pairs.
    private function rankByType($rows)
    {
        $types = DocumentType::whereIn('id', $rows->pluck('document_type_id'))->get(['id', 'name', 'color'])->keyBy('id');
        return $rows->map(fn ($r) => [
            'label' => $types[$r->document_type_id]->name ?? '—',
            'value' => (float) $r->v,
            // Each bar is drawn in its document type's own color.
            'color' => $types[$r->document_type_id]?->badge_color,
        ]);
    }

    // Returns [$from, $to, $rangeKey] Carbon bounds for the selected range.
    private function resolveRange(Request $request): array
    {
        $range = $request->get('range', 'week');

        return match ($range) {
            'today'  => [now()->startOfDay(), now()->endOfDay(), 'today'],
            'month'  => [now()->startOfMonth(), now()->endOfMonth(), 'month'],
            'custom' => [
                $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->startOfWeek(),
                $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()     : now()->endOfDay(),
                'custom',
            ],
            // Default: this week.
            default  => [now()->startOfWeek(), now()->endOfWeek(), 'week'],
        };
    }
}
