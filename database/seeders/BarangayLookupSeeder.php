<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Default lookup data every barangay (SaaS tenant) needs to function:
 * civil statuses, approval statuses, and starter address zones (Puroks 1–6).
 * Safe to re-run — uses upsert so it won't duplicate.
 */
class BarangayLookupSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('eb_civil')->upsert([
            ['id' => 1, 'description' => 'Single'],
            ['id' => 2, 'description' => 'Married'],
            ['id' => 3, 'description' => 'Widowed'],
            ['id' => 4, 'description' => 'Separated'],
            ['id' => 5, 'description' => 'Annulled'],
            ['id' => 6, 'description' => 'Live-in'],
        ], ['id'], ['description']);

        DB::table('eb_approval_status')->upsert([
            ['id' => 1, 'description' => 'Pending'],
            ['id' => 2, 'description' => 'Approved'],
            ['id' => 3, 'description' => 'Rejected'],
        ], ['id'], ['description']);

        DB::table('eb_address')->upsert([
        ], ['id'], ['description', 'is_subd', 'is_active']);
    }
}
