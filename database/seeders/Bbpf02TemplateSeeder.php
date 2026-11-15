<?php

namespace Database\Seeders;

use App\Models\BudgetProgram;
use App\Models\BudgetTemplate;
use App\Models\BudgetTemplateItem;
use App\Models\CoaAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed the standard BBPF02 worksheet — the programs, sub-programs and the
 * accounts listed under each — extracted from the DBM Barangay Budget
 * Preparation Form 2 workbook.
 *
 * Only the standard account rows are seeded. Barangay-specific projects from
 * the source workbook (named activities such as a particular building
 * rehabilitation) are left out: those are entered per barangay, per year.
 */
class Bbpf02TemplateSeeder extends Seeder
{
    private const DATA = __DIR__ . '/data/bbpf02_template.json';

    /** Programs whose item lists are barangay-specific project plans. */
    private const PROJECT_PLAN_PROGRAMS = [
        'Appropriation for Development Fund (RA 7160)',
    ];

    public function run(): void
    {
        if (! is_readable(self::DATA)) {
            $this->command?->error('BBPF02 template data not found at ' . self::DATA);
            return;
        }

        $tree = json_decode(file_get_contents(self::DATA), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($tree) {
            $template = BudgetTemplate::firstOrCreate(
                ['name' => 'BBPF02 — Standard Barangay Budget'],
                [
                    'description' => 'Programmed Appropriation by PPA, per DBM Barangay Budget Preparation Form 2. Accounts listed here are copied into each new fiscal year with blank amounts.',
                    'is_default'  => true,
                    'is_active'   => true,
                ]
            );

            // Rebuild this template's items so re-running picks up edits to
            // the source data without duplicating rows.
            $template->items()->delete();

            $validCodes = CoaAccount::pluck('code')->flip();
            $skipped    = [];
            $order      = 0;

            foreach ($tree as $rootIndex => $root) {
                $rootProgram = $this->program($root['name'], $root['section_label'], null, $rootIndex + 1);

                $this->items($template->id, $rootProgram->id, $root['items'], $validCodes, $skipped, $order);

                foreach ($root['children'] as $childIndex => $child) {
                    $childProgram = $this->program(
                        $child['name'],
                        $child['section_label'],
                        $rootProgram->id,
                        $childIndex + 1
                    );

                    if (in_array($child['name'], self::PROJECT_PLAN_PROGRAMS, true)) {
                        continue;
                    }

                    $this->items($template->id, $childProgram->id, $child['items'], $validCodes, $skipped, $order);
                }
            }

            $count = BudgetTemplateItem::where('template_id', $template->id)->count();
            $this->command?->info("BBPF02 template seeded: {$count} accounts.");

            foreach ($skipped as $note) {
                $this->command?->warn($note);
            }
        });
    }

    private function program(string $name, ?string $section, ?int $parentId, int $sort): BudgetProgram
    {
        $program = BudgetProgram::firstOrNew([
            'name'      => $name,
            'parent_id' => $parentId,
        ]);

        $program->section_label = $section;
        $program->sort_order    = $sort;
        $program->is_active     = true;
        $program->special_fund  = $program->special_fund ?? $this->specialFund($name);
        $program->save();

        return $program;
    }

    /** Link a sub-program to its mandatory fund so ceilings still compute. */
    private function specialFund(string $name): ?string
    {
        return match (true) {
            str_contains($name, 'Development Fund')       => 'dev_fund',
            str_contains($name, 'Sangguniang Kabataan')   => 'sk_fund',
            str_contains($name, 'BDRRM')                  => 'calamity',
            str_contains($name, 'GAD')                    => 'gad',
            default                                       => null,
        };
    }

    private function items(int $templateId, int $programId, array $items, $validCodes, array &$skipped, int &$order): void
    {
        foreach ($items as $item) {
            $code = $item['object_code'];

            // The source workbook has a handful of AIP codes typed into the
            // Account Code column. Keep the row, drop the bad code rather
            // than storing a value that would fail the COA foreign key.
            if ($code !== null && ! $validCodes->has($code)) {
                $skipped[] = "Unknown COA code '{$code}' on \"{$item['name']}\" — row kept without an account code.";
                $code = null;
            }

            BudgetTemplateItem::create([
                'template_id'           => $templateId,
                'program_id'            => $programId,
                'object_class'          => $item['object_class'],
                'name'                  => $item['name'],
                'object_code'           => $code,
                'aip_code'              => $item['aip_code'],
                'expected_result'       => $item['expected_result'],
                'performance_indicator' => $item['performance_indicator'],
                'group_label'           => $item['group_label'],
                'sort_order'            => ++$order,
            ]);
        }
    }
}
