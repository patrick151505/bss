<?php

namespace App\Http\Controllers;

use App\Models\BudgetLineItem;
use App\Models\BudgetLog;
use App\Models\BudgetProgram;
use App\Models\FiscalYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetLineItemController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYears = FiscalYear::orderByDesc('year')->get();
        $fy          = FiscalYear::find($request->fy) ?? FiscalYear::active() ?? $fiscalYears->first();
        $programs    = BudgetProgram::active()->get();

        $lineItems = $fy
            ? BudgetLineItem::where('fiscal_year_id', $fy->id)
                ->with(['transactionLines.transaction'])
                ->orderBy('program_id')->orderBy('object_class')->orderBy('sort_order')
                ->get()
                ->groupBy('program_id')
            : collect();

        return view('budget.line-items.index', compact('fiscalYears', 'fy', 'programs', 'lineItems'));
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
            'object_class'   => 'required|in:PS,MOOE,CO',
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
            'object_class'  => 'required|in:PS,MOOE,CO',
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

    public function seedDefaults(Request $request)
    {
        $data = $request->validate([
            'fiscal_year_id' => 'required|exists:eb_fiscal_years,id',
            'program_id'     => 'required|exists:eb_budget_programs,id',
            'object_class'   => 'required|in:PS,MOOE,CO',
        ]);

        $defaults = BudgetLineItem::DEFAULTS[$data['object_class']] ?? [];
        $added    = 0;
        $newItems = [];

        DB::transaction(function () use ($data, $defaults, &$added, &$newItems) {
            foreach ($defaults as $idx => $entry) {
                [$name, $code] = $entry;
                $already = BudgetLineItem::where([
                    'fiscal_year_id' => $data['fiscal_year_id'],
                    'program_id'     => $data['program_id'],
                    'object_class'   => $data['object_class'],
                    'name'           => $name,
                ])->exists();

                if (!$already) {
                    $item = BudgetLineItem::create([
                        'fiscal_year_id' => $data['fiscal_year_id'],
                        'program_id'     => $data['program_id'],
                        'object_class'   => $data['object_class'],
                        'object_code'    => $code,
                        'name'           => $name,
                        'appropriation'  => 0,
                        'sort_order'     => $idx + 1,
                    ]);
                    $newItems[] = $item;
                    $added++;
                }
            }
        });

        return response()->json([
            'items'   => $newItems,
            'message' => $added > 0 ? "{$added} default line items added." : "All defaults already exist.",
        ]);
    }
}
