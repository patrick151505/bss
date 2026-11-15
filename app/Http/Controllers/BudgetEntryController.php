<?php

namespace App\Http\Controllers;

use App\Models\BudgetLineItem;
use App\Models\BudgetProgram;
use App\Models\FiscalYear;
use Illuminate\Http\Request;

/**
 * BBPF02 — Programmed Appropriation by PPA.
 *
 * One scrolling worksheet per fiscal year, mirroring the DBM form: every
 * account the barangay may spend on is listed, and the treasurer fills in
 * only the Proposed Amount column.
 */
class BudgetEntryController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYears = FiscalYear::orderByDesc('year')->get();
        $fy          = FiscalYear::find($request->fy) ?? FiscalYear::active() ?? $fiscalYears->first();

        $programs = BudgetProgram::roots()
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $itemsByProgram = $fy
            ? BudgetLineItem::where('fiscal_year_id', $fy->id)
                ->orderBy('sort_order')
                ->get()
                ->groupBy('program_id')
            : collect();

        return view('budget.entry.index', compact('fiscalYears', 'fy', 'programs', 'itemsByProgram'));
    }
}
