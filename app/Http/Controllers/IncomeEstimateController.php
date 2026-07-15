<?php

namespace App\Http\Controllers;

use App\Models\BudgetLog;
use App\Models\BudgetSetting;
use App\Models\FiscalYear;
use App\Models\IncomeEstimate;
use Illuminate\Http\Request;

class IncomeEstimateController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYears = FiscalYear::orderByDesc('year')->get();
        $activeFy    = FiscalYear::active() ?? $fiscalYears->first();

        $selectedFyId = $request->get('fy', $activeFy?->id);
        $fy           = FiscalYear::findOrFail($selectedFyId);

        $estimates = IncomeEstimate::where('fiscal_year_id', $fy->id)
            ->orderBy('source_type')
            ->orderBy('source_label')
            ->get();

        $pools     = IncomeEstimate::computePools($estimates);
        $settings  = BudgetSetting::instance();
        $mandatory = $settings->computeMandatoryFunds($pools);

        return view('budget.income-estimates.index', compact(
            'fiscalYears', 'fy', 'estimates', 'pools', 'mandatory', 'settings'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fiscal_year_id'    => 'required|exists:eb_fiscal_years,id',
            'source_type'       => 'required|in:nta,rpt,clearance,other',
            'source_label'      => 'required|string|max:255',
            'estimated_amount'    => 'required|numeric|min:0',
            'two_years_ago_actual'=> 'nullable|numeric|min:0',
            'prior_year_actual'   => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string',
        ]);

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        IncomeEstimate::create($data);

        BudgetLog::record('created', 'income_estimate', 0, "Added: {$data['source_label']} — ₱{$data['estimated_amount']}");

        return back()->with('success', 'Income source added.');
    }

    public function update(Request $request, IncomeEstimate $incomeEstimate)
    {
        $data = $request->validate([
            'source_type'         => 'required|in:nta,rpt,clearance,other',
            'source_label'        => 'required|string|max:255',
            'estimated_amount'    => 'required|numeric|min:0',
            'two_years_ago_actual'=> 'nullable|numeric|min:0',
            'prior_year_actual'   => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string',
        ]);

        $data['updated_by'] = auth()->id();
        $incomeEstimate->update($data);

        return back()->with('success', 'Income source updated.');
    }

    public function destroy(IncomeEstimate $incomeEstimate)
    {
        $incomeEstimate->delete();
        return back()->with('success', 'Income source removed.');
    }
}
