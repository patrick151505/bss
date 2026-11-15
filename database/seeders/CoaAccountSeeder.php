<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoaAccountSeeder extends Seeder
{
    /** Source CSV, relative to the project root. */
    private const CSV = 'lgu_chart_of_accounts_2015_009_budget_master.csv';

    /**
     * Load the COA Revised Chart of Accounts for LGUs (COA Circular 2015-009)
     * into eb_coa_accounts. Idempotent — safe to re-run.
     */
    public function run(): void
    {
        $path = base_path(self::CSV);

        if (! is_readable($path)) {
            $this->command?->error("COA chart of accounts not found at {$path}");
            return;
        }

        $handle = fopen($path, 'r');

        // Strip the UTF-8 BOM so the first header cell reads as "code".
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        $header = array_map(fn ($h) => trim((string) $h), $header);

        $rows = [];
        $now  = now();

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || count($row) < count($header)) {
                continue;
            }

            $data = array_combine($header, array_slice($row, 0, count($header)));
            $code = trim((string) $data['code']);

            if ($code === '') {
                continue;
            }

            $rows[] = [
                'code'          => $code,
                'title'         => trim((string) $data['account_title']),
                'account_type'  => trim((string) $data['account_type']),
                'account_group' => trim((string) $data['account_group']) ?: null,
                'object_class'  => $this->objectClass($code, trim((string) $data['account_type'])),
                'is_budgetable' => $this->truthy($data['is_budgetable'] ?? 'True'),
                'is_active'     => $this->truthy($data['is_active'] ?? 'True'),
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        fclose($handle);

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('eb_coa_accounts')->upsert(
                $chunk,
                ['code'],
                ['title', 'account_type', 'account_group', 'object_class', 'is_budgetable', 'is_active', 'updated_at']
            );
        }

        $this->command?->info(count($rows) . ' COA accounts loaded.');
    }

    /**
     * Map a COA code to the BBPF02 expense class it belongs to.
     * 5-01 = Personal Services, 5-02 = MOOE, 5-03 = Financial Expenses,
     * 1-xx assets = Capital Outlay. Everything else is not budgeted against
     * on BBPF02 (income, non-cash expenses such as depreciation, losses).
     */
    private function objectClass(string $code, string $type): ?string
    {
        return match (true) {
            str_starts_with($code, '5-01') => 'PS',
            str_starts_with($code, '5-02') => 'MOOE',
            str_starts_with($code, '5-03') => 'FE',
            $type === 'Asset'              => 'CO',
            default                        => null,
        };
    }

    private function truthy(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['true', '1', 'yes'], true);
    }
}
