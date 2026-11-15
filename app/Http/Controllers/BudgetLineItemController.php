<?php

namespace App\Http\Controllers;

use App\Models\BudgetLineItem;
use App\Models\BudgetLog;
use App\Models\BudgetProgram;
use App\Models\FiscalYear;
use App\Services\BudgetTemplateApplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetLineItemController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYears = FiscalYear::orderByDesc('year')->get();
        $fy          = FiscalYear::find($request->fy) ?? FiscalYear::active() ?? $fiscalYears->first();
        $programs    = BudgetProgram::active()->get();

        // Only funded accounts appear here. The full menu of accounts —
        // including the ones listed but not funded this year — lives on the
        // BBPF02 entry screen; an account earns a place in the budget proper
        // once it is given an amount.
        $lineItems = $fy
            ? BudgetLineItem::where('fiscal_year_id', $fy->id)
                ->funded()
                ->with(['transactionLines.transaction'])
                ->orderBy('program_id')->orderBy('object_class')->orderBy('sort_order')
                ->get()
                ->groupBy('program_id')
            : collect();

        $unfundedCount = $fy
            ? BudgetLineItem::where('fiscal_year_id', $fy->id)->unfunded()->count()
            : 0;

        return view('budget.line-items.index', compact('fiscalYears', 'fy', 'programs', 'lineItems', 'unfundedCount'));
    }

    /** AJAX: return all line items for a program */
    public function programItems(Request $request)
    {
        $request->validate([
            'fiscal_year_id' => 'required|exists:eb_fiscal_years,id',
            'program_id'     => 'required|exists:eb_budget_programs,id',
        ]);

        $items = BudgetLineItem::where([
            'fiscal_year_id' => $request->fiscal_year_id,
            'program_id'     => $request->program_id,
        ])->orderBy('object_class')->orderBy('sort_order')->get();

        return response()->json(['items' => $items]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fiscal_year_id' => 'required|exists:eb_fiscal_years,id',
            'program_id'     => 'required|exists:eb_budget_programs,id',
            'object_class'   => 'required|in:PS,MOOE,FE,CO',
            'object_code'    => 'nullable|string|max:20',
            'name'           => 'required|string|max:255',
            'appropriation'  => 'required|numeric|min:0',
        ]);

        $exists = BudgetLineItem::where([
            'fiscal_year_id' => $data['fiscal_year_id'],
            'program_id'     => $data['program_id'],
            'object_class'   => $data['object_class'],
            'name'           => $data['name'],
        ])->exists();

        if ($exists) {
            return response()->json(['error' => 'A line item with this name already exists under the same program and object class.'], 422);
        }

        $nextOrder = BudgetLineItem::where([
            'fiscal_year_id' => $data['fiscal_year_id'],
            'program_id'     => $data['program_id'],
            'object_class'   => $data['object_class'],
        ])->max('sort_order') + 1;

        $item = BudgetLineItem::create(array_merge($data, ['sort_order' => $nextOrder]));

        $program = BudgetProgram::find($data['program_id']);
        BudgetLog::record('created', 'budget_line_item', $item->id, "Added: {$data['name']} ({$data['object_class']}) under {$program->name}");

        return response()->json(['item' => $item, 'message' => "Line item \"{$data['name']}\" added."]);
    }

    public function update(Request $request, BudgetLineItem $lineItem)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'object_class'  => 'required|in:PS,MOOE,FE,CO',
            'object_code'   => 'nullable|string|max:20',
            'appropriation' => 'required|numeric|min:0',
        ]);

        $exists = BudgetLineItem::where([
            'fiscal_year_id' => $lineItem->fiscal_year_id,
            'program_id'     => $lineItem->program_id,
            'object_class'   => $data['object_class'],
            'name'           => $data['name'],
        ])->where('id', '!=', $lineItem->id)->exists();

        if ($exists) {
            return response()->json(['error' => 'Another line item with this name already exists.'], 422);
        }

        $lineItem->update($data);

        BudgetLog::record('updated', 'budget_line_item', $lineItem->id, "Updated: {$data['name']} — ₱" . number_format($data['appropriation'], 2));

        return response()->json(['item' => $lineItem->fresh(), 'message' => 'Line item updated.']);
    }

    public function destroy(BudgetLineItem $lineItem)
    {
        if ($lineItem->transactionLines()->exists()) {
            return response()->json(['error' => 'Cannot delete — this line item has existing voucher entries.'], 422);
        }
        // Cash advance check re-enabled when CA module is built

        $name = $lineItem->name;
        $id   = $lineItem->id;
        $lineItem->delete();

        BudgetLog::record('deleted', 'budget_line_item', 0, "Deleted line item: {$name}");

        return response()->json(['id' => $id, 'message' => "Line item \"{$name}\" deleted."]);
    }

    /**
     * Copy the standard BBPF02 account list into this fiscal year. Accounts
     * arrive with a blank appropriation — the treasurer types amounts only on
     * the rows they fund, and unfunded rows stay listed on the form.
     */
    public function seedDefaults(Request $request, BudgetTemplateApplier $applier)
    {
        $data = $request->validate([
            'fiscal_year_id' => 'required|exists:eb_fiscal_years,id',
            'program_id'     => 'nullable|exists:eb_budget_programs,id',
        ]);

        $result = $applier->apply($data['fiscal_year_id'], null, $data['program_id'] ?? null);

        if ($result['created'] === 0) {
            return response()->json([
                'items'   => [],
                'message' => $result['skipped'] > 0
                    ? 'All template accounts are already listed.'
                    : 'No budget template is configured.',
            ]);
        }

        $items = BudgetLineItem::where('fiscal_year_id', $data['fiscal_year_id'])
            ->when($data['program_id'] ?? null, fn ($q, $p) => $q->where('program_id', $p))
            ->orderBy('sort_order')
            ->get();

        BudgetLog::record('created', 'budget_line_item', 0, "Applied budget template: {$result['created']} accounts listed.");

        return response()->json([
            'items'   => $items,
            'message' => "{$result['created']} accounts listed from the template.",
        ]);
    }

    /**
     * Bulk-save the Proposed Amount column from the BBPF02 entry grid.
     * A blank amount stores null — "listed but not funded" — which the form
     * keeps and prints, and which is distinct from a deliberate 0.00.
     */
    public function saveAmounts(Request $request)
    {
        $data = $request->validate([
            'amounts'               => 'required|array',
            'amounts.*.id'          => 'required|integer|exists:eb_budget_line_items,id',
            'amounts.*.amount'      => 'nullable|numeric|min:0',
        ]);

        $changed = 0;

        DB::transaction(function () use ($data, &$changed) {
            $items = BudgetLineItem::whereIn('id', array_column($data['amounts'], 'id'))
                ->get()
                ->keyBy('id');

            foreach ($data['amounts'] as $row) {
                $item = $items->get($row['id']);

                if (! $item) {
                    continue;
                }

                $new = $row['amount'] === null || $row['amount'] === ''
                    ? null
                    : round((float) $row['amount'], 2);

                $old = $item->appropriation === null ? null : round((float) $item->appropriation, 2);

                if ($new === $old) {
                    continue;
                }

                $item->update(['appropriation' => $new]);
                $changed++;
            }
        });

        return response()->json([
            'message' => $changed > 0 ? "{$changed} amount(s) saved." : 'No changes.',
            'changed' => $changed,
        ]);
    }
}
