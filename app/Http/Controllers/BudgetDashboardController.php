<?php

namespace App\Http\Controllers;

use App\Models\BudgetLineItem;
use App\Models\BudgetTransaction;
use App\Models\CashAdvance;
use App\Models\FiscalYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetDashboardController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYears = FiscalYear::orderByDesc('year')->get();
        $activeFy    = FiscalYear::find($request->fy) ?? FiscalYear::active() ?? $fiscalYears->first();

        if (!$activeFy) {
            return view('budget.dashboard', [
                'fiscalYears'     => $fiscalYears,
                'activeFy'        => null,
                'allocationRows'  => collect(),
                'byClass'         => collect(),
                'byProgram'       => collect(),
                'totals'          => ['appropriation' => 0, 'disbursed' => 0, 'balance' => 0, 'tax' => 0, 'utilization' => 0],
                'recentTx'        => collect(),
                'openCas'         => collect(),
                'overdueCas'      => collect(),
                'statusCounts'    => collect(),
                'pendingCount'    => 0,
                'chartData'       => $this->emptyChart(),
            ]);
        }

        $fyId = $activeFy->id;

        // "Disbursed" = money actually paid out or approved for payment.
        $disbursedStatuses = ['approved', 'paid'];

        // Budget line items (the live budget structure), with their program.
        $lineItems = BudgetLineItem::with('program')
            ->where('fiscal_year_id', $fyId)
            ->orderBy('object_class')
            ->orderBy('sort_order')
            ->get();

        // Disbursement per line item = SUM of voucher lines whose parent voucher
        // is approved/paid, grouped by line_item_id.
        $disbursedByLine = DB::table('eb_budget_transaction_lines as tl')
            ->join('eb_budget_transactions as t', 't.id', '=', 'tl.transaction_id')
            ->where('t.fiscal_year_id', $fyId)
            ->whereIn('t.status', $disbursedStatuses)
            ->groupBy('tl.line_item_id')
            ->selectRaw('tl.line_item_id, SUM(tl.amount) as total')
            ->pluck('total', 'tl.line_item_id');

        // Total tax withheld across disbursed vouchers (voucher-level, not per line).
        $totalTax = (float) BudgetTransaction::where('fiscal_year_id', $fyId)
            ->whereIn('status', $disbursedStatuses)
            ->sum('tax_withheld');

        $allocationRows = collect();
        $totals = ['appropriation' => 0, 'disbursed' => 0, 'balance' => 0, 'tax' => $totalTax];

        foreach ($lineItems as $item) {
            $appropriation = (float) $item->appropriation;
            $disbursed = (float) ($disbursedByLine[$item->id] ?? 0);
            $balance   = $appropriation - $disbursed;

            $allocationRows->push((object) [
                'alloc'    => $item,            // a BudgetLineItem (has ->name, ->object_class, ->program)
                'adjusted' => $appropriation,
                'disbursed'=> $disbursed,
                'tax'      => 0,
                'balance'  => $balance,
                'pct'      => $appropriation > 0 ? min(100, round($disbursed / $appropriation * 100)) : 0,
            ]);

            $totals['appropriation'] += $appropriation;
            $totals['disbursed']     += $disbursed;
            $totals['balance']       += $balance;
        }

        // Overall utilization %.
        $totals['utilization'] = $totals['appropriation'] > 0
            ? min(100, round($totals['disbursed'] / $totals['appropriation'] * 100, 1))
            : 0;

        // Spending grouped by object class (PS / MOOE / CO) for the donut + cards.
        $byClass = collect(['PS', 'MOOE', 'CO'])->mapWithKeys(function ($class) use ($allocationRows) {
            $rows = $allocationRows->filter(fn($r) => $r->alloc->object_class === $class);
            return [$class => (object) [
                'appropriation' => $rows->sum('adjusted'),
                'disbursed'     => $rows->sum('disbursed'),
                'balance'       => $rows->sum('balance'),
            ]];
        });

        // Program-level rollup (appropriation vs disbursed vs balance per program).
        $byProgram = $allocationRows
            ->groupBy(fn($r) => $r->alloc->program?->name ?? 'Unassigned')
            ->map(fn($rows, $name) => (object) [
                'name'          => $name,
                'appropriation' => $rows->sum('adjusted'),
                'disbursed'     => $rows->sum('disbursed'),
                'balance'       => $rows->sum('balance'),
                'pct'           => $rows->sum('adjusted') > 0
                                    ? min(100, round($rows->sum('disbursed') / $rows->sum('adjusted') * 100))
                                    : 0,
            ])
            ->sortByDesc('appropriation')
            ->values();

        // Voucher status counts (surface pending/draft that need approval).
        $statusCounts = BudgetTransaction::where('fiscal_year_id', $fyId)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');
        $pendingCount = (int) ($statusCounts['draft'] ?? 0);

        // Recent transactions.
        $recentTx = BudgetTransaction::with('lines.lineItem')
            ->where('fiscal_year_id', $fyId)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        // Cash advance alerts.
        $openCas   = CashAdvance::with('officer')
            ->where('fiscal_year_id', $fyId)
            ->where('status', 'open')
            ->orderBy('deadline_date')
            ->get();

        $overdueCas = $openCas->filter(fn($ca) => $ca->isOverdue());

        // Monthly disbursement chart.
        $monthly = BudgetTransaction::where('fiscal_year_id', $fyId)
            ->whereIn('status', $disbursedStatuses)
            ->selectRaw('MONTH(transaction_date) as m, SUM(amount) as total')
            ->groupBy(DB::raw('MONTH(transaction_date)'))
            ->pluck('total', 'm');

        $chartData = [
            'labels'   => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            'disbursed'=> array_map(fn($m) => (float) ($monthly[$m] ?? 0), range(1, 12)),
            'byClass'  => [
                'labels' => $byClass->keys()->all(),
                'data'   => $byClass->map(fn($c) => (float) $c->disbursed)->values()->all(),
            ],
        ];

        return view('budget.dashboard', compact(
            'fiscalYears', 'activeFy',
            'allocationRows', 'byClass', 'byProgram', 'totals',
            'recentTx', 'openCas', 'overdueCas', 'chartData',
            'statusCounts', 'pendingCount'
        ));
    }

    private function emptyChart(): array
    {
        return [
            'labels'    => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            'disbursed' => array_fill(0, 12, 0),
            'byClass'   => ['labels' => [], 'data' => []],
        ];
    }
}
