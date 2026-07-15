<?php

namespace App\Http\Controllers;

use App\Models\BudgetLineItem;
use App\Models\BudgetLog;
use App\Models\BudgetProgram;
use App\Models\BudgetSetting;
use App\Models\FiscalYear;
use App\Models\IncomeEstimate;
use Illuminate\Http\Request;

class BudgetAllocationController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYears = FiscalYear::orderByDesc('year')->get();
        $fy          = FiscalYear::find($request->fy) ?? FiscalYear::active() ?? $fiscalYears->first();

        $programs = BudgetProgram::active()->get();
        $settings = BudgetSetting::instance();

        $estimates = $fy ? IncomeEstimate::where('fiscal_year_id', $fy->id)->get() : collect();
        $pools     = IncomeEstimate::computePools($estimates);
        $mandatory = $settings->computeMandatoryFunds($pools);

        // Line items for this FY, grouped by program_id (flat list per program)
        $lineItems = $fy
            ? BudgetLineItem::where('fiscal_year_id', $fy->id)
                ->orderBy('program_id')
                ->orderBy('object_class')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('program_id')
            : collect();

        // Column totals
        $colTotals = ['PS' => 0, 'MOOE' => 0, 'CO' => 0];
        foreach ($lineItems as $items) {
            foreach ($items as $item) {
                $colTotals[$item->object_class] = ($colTotals[$item->object_class] ?? 0) + (float) $item->appropriation;
            }
        }
        $grandTotal  = array_sum($colTotals);
        $totalBudget = $pools['total_budget'];
        $psCap       = $totalBudget > 0 ? round($colTotals['PS'] / $totalBudget * 100, 1) : 0;

        return view('budget.allocations.index', compact(
            'fiscalYears', 'fy', 'programs', 'lineItems',
            'colTotals', 'grandTotal', 'totalBudget', 'psCap',
            'mandatory', 'pools'
        ));
    }
}
