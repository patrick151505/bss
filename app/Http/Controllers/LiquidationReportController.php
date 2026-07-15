<?php

namespace App\Http\Controllers;

use App\Models\CashAdvance;
use App\Models\LiquidationReport;
use App\Models\LiquidationLine;
use App\Models\BudgetLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LiquidationReportController extends Controller
{
    public function create(CashAdvance $cashAdvance)
    {
        if (!$cashAdvance->isOpen()) {
            return back()->with('error', 'This cash advance is already liquidated or cancelled.');
        }
        if ($cashAdvance->liquidationReport) {
            return redirect()->route('budget.liquidations.edit', $cashAdvance->liquidationReport);
        }
        return view('budget.liquidations.create', compact('cashAdvance'));
    }

    public function store(Request $request, CashAdvance $cashAdvance)
    {
        if (!$cashAdvance->isOpen()) {
            return back()->with('error', 'Cash advance is not open.');
        }

        $data = $request->validate([
            'liquidation_date'  => 'required|date',
            'refund_amount'     => 'nullable|numeric|min:0',
            'refund_or_no'      => 'nullable|string|max:100',
            'notes'             => 'nullable|string',
            'lines'             => 'required|array|min:1',
            'lines.*.or_no'     => 'nullable|string|max:100',
            'lines.*.receipt_date' => 'nullable|date',
            'lines.*.particulars'  => 'required|string|max:500',
            'lines.*.amount'       => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($cashAdvance, $data) {
            $totalExpenses = collect($data['lines'])->sum('amount');
            $refund        = (float) ($data['refund_amount'] ?? 0);

            $report = LiquidationReport::create([
                'cash_advance_id'  => $cashAdvance->id,
                'liquidation_date' => $data['liquidation_date'],
                'total_expenses'   => $totalExpenses,
                'refund_amount'    => $refund,
                'refund_or_no'     => $data['refund_or_no'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'status'           => 'draft',
                'created_by'       => auth()->id(),
            ]);

            foreach ($data['lines'] as $line) {
                $report->lines()->create($line);
            }
        });

        return redirect()->route('budget.cash-advances.show', $cashAdvance)
            ->with('success', 'Liquidation report saved as draft.');
    }

    public function show(LiquidationReport $liquidation)
    {
        $liquidation->load(['cashAdvance.officer', 'lines']);
        return view('budget.liquidations.show', compact('liquidation'));
    }

    public function edit(LiquidationReport $liquidation)
    {
        if ($liquidation->status === 'closed') {
            return back()->with('error', 'Closed liquidation reports cannot be edited.');
        }
        $liquidation->load(['cashAdvance', 'lines']);
        return view('budget.liquidations.edit', compact('liquidation'));
    }

    public function update(Request $request, LiquidationReport $liquidation)
    {
        if ($liquidation->status === 'closed') {
            return back()->with('error', 'Closed liquidation reports cannot be edited.');
        }

        $data = $request->validate([
            'liquidation_date'  => 'required|date',
            'refund_amount'     => 'nullable|numeric|min:0',
            'refund_or_no'      => 'nullable|string|max:100',
            'notes'             => 'nullable|string',
            'lines'             => 'required|array|min:1',
            'lines.*.or_no'     => 'nullable|string|max:100',
            'lines.*.receipt_date' => 'nullable|date',
            'lines.*.particulars'  => 'required|string|max:500',
            'lines.*.amount'       => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($liquidation, $data) {
            $totalExpenses = collect($data['lines'])->sum('amount');
            $refund        = (float) ($data['refund_amount'] ?? 0);

            $liquidation->update([
                'liquidation_date' => $data['liquidation_date'],
                'total_expenses'   => $totalExpenses,
                'refund_amount'    => $refund,
                'refund_or_no'     => $data['refund_or_no'] ?? null,
                'notes'            => $data['notes'] ?? null,
            ]);

            $liquidation->lines()->delete();
            foreach ($data['lines'] as $line) {
                $liquidation->lines()->create($line);
            }
        });

        return redirect()->route('budget.liquidations.show', $liquidation)->with('success', 'Liquidation report updated.');
    }

    public function close(Request $request, LiquidationReport $liquidation)
    {
        if ($liquidation->status === 'closed') {
            return back()->with('error', 'Already closed.');
        }

        // Hard block: must reconcile to zero
        if (!$liquidation->canClose()) {
            $bal = number_format(abs($liquidation->reconciliationBalance()), 2);
            return back()->with('error', "Cannot close: reconciliation balance is ₱{$bal}. Expenses + refund must equal the cash advance amount.");
        }

        DB::transaction(function () use ($liquidation) {
            $liquidation->update(['status' => 'closed']);
            $liquidation->cashAdvance()->update(['status' => 'liquidated']);
            BudgetLog::record('liquidation.closed', $liquidation, [
                'ca_no'          => $liquidation->cashAdvance->ca_no,
                'total_expenses' => $liquidation->total_expenses,
                'refund_amount'  => $liquidation->refund_amount,
            ]);
        });

        return redirect()->route('budget.cash-advances.show', $liquidation->cashAdvance)
            ->with('success', 'Liquidation closed. Cash advance marked as liquidated.');
    }
}
