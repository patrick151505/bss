<?php

namespace App\Http\Controllers;

use App\Models\BudgetLog;
use App\Models\FiscalYear;
use Illuminate\Http\Request;

class FiscalYearController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'year'                   => 'required|integer|min:2000|max:2100|unique:eb_fiscal_years,year',
            'label'                  => 'nullable|max:100',
            'beginning_cash_balance' => 'nullable|numeric|min:0',
            'notes'                  => 'nullable|max:500',
        ]);

        $fy = FiscalYear::create([
            'year'                   => $request->year,
            'label'                  => $request->label,
            'beginning_cash_balance' => $request->beginning_cash_balance ?? 0,
            'notes'                  => $request->notes,
            'is_active'              => false,
        ]);

        BudgetLog::record('fiscal_year.created', $fy, ['year' => $fy->year]);

        return back()->with('success', "Fiscal year {$fy->year} added.");
    }

    public function setActive(FiscalYear $fiscalYear)
    {
        FiscalYear::query()->update(['is_active' => false]);
        $fiscalYear->update(['is_active' => true]);

        BudgetLog::record('fiscal_year.activated', $fiscalYear, ['year' => $fiscalYear->year]);

        return back()->with('success', "FY {$fiscalYear->year} is now the active fiscal year.");
    }

    public function destroy(FiscalYear $fiscalYear)
    {
        if ($fiscalYear->is_active) {
            return back()->with('error', 'Cannot delete the active fiscal year.');
        }
        if ($fiscalYear->transactions()->exists() || $fiscalYear->cashAdvances()->exists()) {
            return back()->with('error', 'Cannot delete a fiscal year that has transactions or cash advances.');
        }

        BudgetLog::record('fiscal_year.deleted', $fiscalYear, ['year' => $fiscalYear->year]);
        $fiscalYear->delete();

        return back()->with('success', "Fiscal year {$fiscalYear->year} deleted.");
    }
}
