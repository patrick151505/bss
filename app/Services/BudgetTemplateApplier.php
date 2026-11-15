<?php

namespace App\Services;

use App\Models\BudgetLineItem;
use App\Models\BudgetTemplate;
use App\Models\BudgetTemplateItem;
use Illuminate\Support\Facades\DB;

/**
 * Copies a budget template into a fiscal year as blank line items.
 *
 * This is what makes the BBPF02 workflow work: every account the barangay
 * may spend on is listed up front with a null appropriation, and the
 * treasurer only types amounts on the rows they actually fund. Unfunded rows
 * stay on the form and print blank, exactly as they do in the workbook.
 */
class BudgetTemplateApplier
{
    /**
     * @return array{created:int, skipped:int}
     */
    public function apply(int $fiscalYearId, ?BudgetTemplate $template = null, ?int $programId = null): array
    {
        $template ??= BudgetTemplate::default();

        if (! $template) {
            return ['created' => 0, 'skipped' => 0];
        }

        $query = BudgetTemplateItem::where('template_id', $template->id)->orderBy('sort_order');

        if ($programId !== null) {
            $query->where('program_id', $programId);
        }

        $items = $query->get();

        // A line item is identified within a fiscal year by its AIP reference
        // code, so re-applying a template tops up missing rows without
        // duplicating or overwriting amounts already entered.
        $existing = BudgetLineItem::where('fiscal_year_id', $fiscalYearId)
            ->pluck('aip_code')
            ->filter()
            ->flip();

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($items, $fiscalYearId, $existing, &$created, &$skipped) {
            foreach ($items as $item) {
                if ($item->aip_code !== null && $existing->has($item->aip_code)) {
                    $skipped++;
                    continue;
                }

                BudgetLineItem::create([
                    'fiscal_year_id'        => $fiscalYearId,
                    'program_id'            => $item->program_id,
                    'object_class'          => $item->object_class,
                    'object_code'           => $item->object_code,
                    'aip_code'              => $item->aip_code,
                    'name'                  => $item->name,
                    // Null, not zero: "listed but not funded this year".
                    'appropriation'         => null,
                    'expected_result'       => $item->expected_result,
                    'performance_indicator' => $item->performance_indicator,
                    'sort_order'            => $item->sort_order,
                ]);

                $created++;
            }
        });

        return ['created' => $created, 'skipped' => $skipped];
    }
}
