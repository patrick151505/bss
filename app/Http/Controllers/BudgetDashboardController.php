<?php

namespace App\Http\Controllers;

use App\Models\BudgetAllocation;
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
                'totals'          => ['appropriation' => 0, 'disbursed' => 0, 'balance' => 0, 'tax' => 0],
                'recentTx'        => collect(),
                'openCas'         => collect(),
                'overdueCas'      => collect(),
                'chartData'       => $this->emptyChart(),
            ]);
        }

        $fyId = $activeFy->id;

        // Appropriation lines with computed disbursed per line
        $allocations = BudgetAllocation::with(['adjustments'])
            ->where('fiscal_year_id', $fyId)
            ->orderBy('object_class')
            ->orderBy('fund_type')
            ->orderBy('line_name')
            ->get();

        // Aggregate disbursements per allocation line
        $disbursedByAlloc = BudgetTransaction::where('fiscal_year_id', $fyId)
            ->where('status', 'approved')
            ->whereNotNull('allocation_id')
            ->selectRaw('allocation_id, SUM(amount) as total, SUM(tax_withheld) as tax')
            ->groupBy('allocation_id')
            ->get()
            ->keyBy('allocation_id');

        $allocationRows = collect();
        $totals = ['appropriation' => 0, 'disbursed' => 0, 'balance' => 0, 'tax' => 0];

        foreach ($allocations as $alloc) {
            $adjusted  = $alloc->adjustedAppropriation();
            $row       = $disbursedByAlloc->get($alloc->id);
            $disbursed = (float) ($row->total ?? 0);
            $tax       = (float) ($row->tax ?? 0);
            $balance   = $adjusted - $disbursed;

            $allocationRows->push((object) [
                'alloc'    => $alloc,
                'adjusted' => $adjusted,
                'disbursed'=> $disbursed,
                'tax'      => $tax,
                'balance'  => $balance,
                'pct'      => $adjusted > 0 ? min(100, round($disbursed / $adjusted * 100)) : 0,
            ]);

            $totals['appropriation'] += $adjusted;
            $totals['disbursed']     += $disbursed;
            $totals['balance']       += $balance;
            $totals['tax']           += $tax;
        }

        // Group by object class for the summary cards
        $byClass = $allocationRows->groupBy(fn($r) => $r->alloc->object_class);

        // Recent transactions
        $recentTx = BudgetTransaction::with('allocation')
            ->where('fiscal_year_id', $fyId)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        // Cash advance alerts
        $openCas   = CashAdvance::with('officer')
            ->where('fiscal_year_id', $fyId)
            ->where('status', 'open')
            ->orderBy('deadline_date')
            ->get();

        $overdueCas = $openCas->filter(fn($ca) => $ca->isOverdue());

        // Monthly disbursement chart
        $monthly = BudgetTransaction::where('fiscal_year_id', $fyId)
            ->where('status', 'approved')
            ->selectRaw('MONTH(transaction_date) as m, SUM(amount) as total')
            ->groupBy(DB::raw('MONTH(transaction_date)'))
            ->pluck('total', 'm');

        $chartData = [
            'labels'   => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            'disbursed'=> array_map(fn($m) => (float) ($monthly[$m] ?? 0), range(1, 12)),
        ];

        return view('budget.dashboard', compact(
            'fiscalYears', 'activeFy',
            'allocationRows', 'byClass', 'totals',
            'recentTx', 'openCas', 'overdueCas', 'chartData'
        ));
    }

    private function emptyChart(): array
    {
        return [
            'labels'    => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            'disbursed' => array_fill(0, 12, 0),
        ];
    }
}
