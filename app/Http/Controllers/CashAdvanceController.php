<?php

namespace App\Http\Controllers;

use App\Models\CashAdvance;
use App\Models\AccountableOfficer;
use App\Models\FiscalYear;
use App\Models\BudgetAllocation;
use App\Models\BudgetLog;
use Illuminate\Http\Request;

class CashAdvanceController extends Controller
{
    public function index()
    {
        $fiscalYear = FiscalYear::active();
        $cashAdvances = CashAdvance::with(['officer', 'allocation', 'fiscalYear'])
            ->when($fiscalYear, fn($q) => $q->where('fiscal_year_id', $fiscalYear->id))
            ->latest()
            ->paginate(20);

        return view('budget.cash-advances.index', compact('cashAdvances', 'fiscalYear'));
    }

    public function create()
    {
        $fiscalYear = FiscalYear::active();
        $officers   = AccountableOfficer::where('is_active', true)->orderBy('name')->get();
        $allocations= $fiscalYear
            ? BudgetAllocation::where('fiscal_year_id', $fiscalYear->id)->orderBy('object_class')->orderBy('line_name')->get()
            : collect();

        return view('budget.cash-advances.create', compact('fiscalYear', 'officers', 'allocations'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fiscal_year_id' => 'required|exists:eb_fiscal_years,id',
            'officer_id'     => 'required|exists:eb_accountable_officers,id',
            'allocation_id'  => 'nullable|exists:eb_budget_allocations,id',
            'purpose'        => 'required|string|max:500',
            'amount'         => 'required|numeric|min:0.01',
            'date_granted'   => 'required|date',
            'deadline_date'  => 'required|date|after_or_equal:date_granted',
            'approved_by'    => 'nullable|string|max:255',
            'reference_no'   => 'nullable|string|max:100',
            'notes'          => 'nullable|string',
        ]);

        // Hard block: officer must not have an open advance
        $officer = AccountableOfficer::findOrFail($data['officer_id']);
        if ($officer->hasOpenAdvance()) {
            return back()->withInput()
                ->with('error', "Cannot grant: {$officer->name} still has an open cash advance.");
        }

        // Hard block: line balance must cover the advance
        if (!empty($data['allocation_id'])) {
            $allocation = BudgetAllocation::findOrFail($data['allocation_id']);
            if ($allocation->balance() < (float) $data['amount']) {
                return back()->withInput()
                    ->with('error', 'Insufficient line balance for this allocation.');
            }
        }

        $data['ca_no']      = CashAdvance::generateCaNo(date('Y', strtotime($data['date_granted'])));
        $data['created_by'] = auth()->id();

        $ca = CashAdvance::create($data);

        BudgetLog::record('cash_advance.created', $ca, ['ca_no' => $ca->ca_no, 'amount' => $ca->amount]);

        return redirect()->route('budget.cash-advances.show', $ca)
            ->with('success', "Cash advance {$ca->ca_no} created.");
    }

    public function show(CashAdvance $cashAdvance)
    {
        $cashAdvance->load(['officer', 'allocation', 'fiscalYear', 'liquidationReport.lines']);
        return view('budget.cash-advances.show', compact('cashAdvance'));
    }

    public function edit(CashAdvance $cashAdvance)
    {
        if (!$cashAdvance->isOpen()) {
            return back()->with('error', 'Only open cash advances can be edited.');
        }
        $officers    = AccountableOfficer::where('is_active', true)->orderBy('name')->get();
        $allocations = BudgetAllocation::where('fiscal_year_id', $cashAdvance->fiscal_year_id)
            ->orderBy('object_class')->orderBy('line_name')->get();
        return view('budget.cash-advances.edit', compact('cashAdvance', 'officers', 'allocations'));
    }

    public function update(Request $request, CashAdvance $cashAdvance)
    {
        if (!$cashAdvance->isOpen()) {
            return back()->with('error', 'Only open cash advances can be edited.');
        }

        $data = $request->validate([
            'purpose'       => 'required|string|max:500',
            'deadline_date' => 'required|date',
            'approved_by'   => 'nullable|string|max:255',
            'reference_no'  => 'nullable|string|max:100',
            'notes'         => 'nullable|string',
        ]);

        $cashAdvance->update($data);
        return redirect()->route('budget.cash-advances.show', $cashAdvance)->with('success', 'Cash advance updated.');
    }
}
