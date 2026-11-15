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

        // Relationship of a family member to the family head. Required when
        // adding a member to a family — without these the household module's
        // "Add Member" dropdown is empty and the save is rejected.
        DB::table('eb_relation')->upsert([
            ['id' => 1,  'description' => 'Spouse'],
            ['id' => 2,  'description' => 'Son'],
            ['id' => 3,  'description' => 'Daughter'],
            ['id' => 4,  'description' => 'Father'],
            ['id' => 5,  'description' => 'Mother'],
            ['id' => 6,  'description' => 'Brother'],
            ['id' => 7,  'description' => 'Sister'],
            ['id' => 8,  'description' => 'Grandfather'],
            ['id' => 9,  'description' => 'Grandmother'],
            ['id' => 10, 'description' => 'Grandson'],
            ['id' => 11, 'description' => 'Granddaughter'],
            ['id' => 12, 'description' => 'Son-in-law'],
            ['id' => 13, 'description' => 'Daughter-in-law'],
            ['id' => 14, 'description' => 'Father-in-law'],
            ['id' => 15, 'description' => 'Mother-in-law'],
            ['id' => 16, 'description' => 'Brother-in-law'],
            ['id' => 17, 'description' => 'Sister-in-law'],
            ['id' => 18, 'description' => 'Uncle'],
            ['id' => 19, 'description' => 'Aunt'],
            ['id' => 20, 'description' => 'Nephew'],
            ['id' => 21, 'description' => 'Niece'],
            ['id' => 22, 'description' => 'Cousin'],
            ['id' => 23, 'description' => 'Stepson'],
            ['id' => 24, 'description' => 'Stepdaughter'],
            ['id' => 25, 'description' => 'Ward / Foster Child'],
            ['id' => 26, 'description' => 'Househelp'],
            ['id' => 27, 'description' => 'Boarder'],
            ['id' => 28, 'description' => 'Other Relative'],
            ['id' => 29, 'description' => 'Non-Relative'],
        ], ['id'], ['description']);
    }
}
