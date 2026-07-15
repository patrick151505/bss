<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('eb_activity_logs')->truncate();

        $now   = Carbon::now();
        $today = Carbon::today();

        // Spread logs across the last 7 days + today
        // user_id: 1=Admin, 2=Secretary Cruz, 3=Treasurer Reyes
        $logs = [

            // ── 7 days ago ───────────────────────────────────────────
            [1, 'created',        'citizen',          1,  'registered citizen Roberto Santos',                    $today->copy()->subDays(6)->setTime(8, 12)],
            [1, 'created',        'citizen',          2,  'registered citizen Maria Cruz',                        $today->copy()->subDays(6)->setTime(8, 35)],
            [2, 'created',        'document_request', 1,  'submitted document request for Roberto Santos',        $today->copy()->subDays(6)->setTime(9, 10)],
            [2, 'updated',        'document_request', 1,  'approved document request #DR-0001',                   $today->copy()->subDays(6)->setTime(9, 45)],
            [1, 'created',        'citizen',          3,  'registered citizen Juan Reyes',                        $today->copy()->subDays(6)->setTime(10, 20)],
            [3, 'created',        'budget_transaction', 1, 'created voucher #BV-2026-001 for ₱15,000.00',        $today->copy()->subDays(6)->setTime(10, 55)],
            [3, 'status_updated', 'budget_transaction', 1, 'approved voucher #BV-2026-001',                      $today->copy()->subDays(6)->setTime(11, 30)],
            [1, 'created',        'citizen',          4,  'registered citizen Ana Bautista',                     $today->copy()->subDays(6)->setTime(13, 15)],
            [2, 'created',        'document_request', 2,  'submitted document request for Maria Cruz',           $today->copy()->subDays(6)->setTime(14, 00)],
            [2, 'status_updated', 'document_request', 2,  'released document for Maria Cruz',                    $today->copy()->subDays(6)->setTime(14, 40)],

            // ── 6 days ago ───────────────────────────────────────────
            [1, 'created',        'citizen',          5,  'registered citizen Pedro Garcia',                      $today->copy()->subDays(5)->setTime(8, 05)],
            [1, 'created',        'citizen',          6,  'registered citizen Liza Mendoza',                     $today->copy()->subDays(5)->setTime(8, 50)],
            [2, 'created',        'blotter',          1,  'filed blotter case #BL-2026-001 — physical_injury',   $today->copy()->subDays(5)->setTime(9, 25)],
            [2, 'action_added',   'blotter',          1,  'added hearing schedule to blotter #BL-2026-001',      $today->copy()->subDays(5)->setTime(9, 50)],
            [3, 'created',        'budget_transaction', 2, 'created voucher #BV-2026-002 for ₱8,500.00',        $today->copy()->subDays(5)->setTime(10, 30)],
            [1, 'updated',        'citizen',          3,  'updated profile of Juan Reyes',                       $today->copy()->subDays(5)->setTime(11, 10)],
            [2, 'created',        'document_request', 5,  'submitted document request for Pedro Garcia',         $today->copy()->subDays(5)->setTime(13, 00)],
            [2, 'created',        'document_request', 10, 'submitted document request for Liza Mendoza',        $today->copy()->subDays(5)->setTime(14, 15)],
            [3, 'status_updated', 'budget_transaction', 2, 'marked voucher #BV-2026-002 as paid',               $today->copy()->subDays(5)->setTime(15, 00)],
            [1, 'created',        'citizen',          7,  'registered citizen Carlo Villanueva',                 $today->copy()->subDays(5)->setTime(15, 40)],

            // ── 5 days ago ───────────────────────────────────────────
            [2, 'created',        'blotter',          2,  'filed blotter case #BL-2026-002 — dispute',          $today->copy()->subDays(4)->setTime(8, 30)],
            [2, 'action_added',   'blotter',          2,  'mediation session scheduled for #BL-2026-002',       $today->copy()->subDays(4)->setTime(9, 00)],
            [1, 'created',        'citizen',          8,  'registered citizen Rosa Flores',                      $today->copy()->subDays(4)->setTime(9, 30)],
            [3, 'created',        'budget_transaction', 3, 'created voucher #BV-2026-003 for ₱22,000.00',      $today->copy()->subDays(4)->setTime(10, 00)],
            [1, 'created',        'citizen',          9,  'registered citizen Ernesto Dela Cruz',                $today->copy()->subDays(4)->setTime(10, 45)],
            [2, 'created',        'document_request', 3,  'submitted document request for Rosa Flores',         $today->copy()->subDays(4)->setTime(11, 20)],
            [2, 'updated',        'document_request', 3,  'approved document request #DR-0003',                 $today->copy()->subDays(4)->setTime(11, 55)],
            [1, 'exported',       'citizen',          null, 'exported citizens list to CSV',                    $today->copy()->subDays(4)->setTime(14, 00)],
            [3, 'status_updated', 'budget_transaction', 3, 'approved voucher #BV-2026-003',                    $today->copy()->subDays(4)->setTime(14, 40)],
            [2, 'created',        'blotter',          3,  'filed blotter case #BL-2026-003 — theft',           $today->copy()->subDays(4)->setTime(15, 20)],

            // ── 4 days ago ───────────────────────────────────────────
            [1, 'created',        'citizen',          10, 'registered citizen Teresa Aquino',                   $today->copy()->subDays(3)->setTime(8, 10)],
            [1, 'updated',        'citizen',          7,  'updated profile of Carlo Villanueva',                $today->copy()->subDays(3)->setTime(8, 50)],
            [2, 'status_updated', 'blotter',          1,  'updated status of blotter #BL-2026-001 to settled', $today->copy()->subDays(3)->setTime(9, 30)],
            [3, 'created',        'budget_transaction', 4, 'created voucher #BV-2026-004 for ₱5,000.00',      $today->copy()->subDays(3)->setTime(10, 15)],
            [2, 'created',        'document_request', 6,  'submitted document request for Teresa Aquino',      $today->copy()->subDays(3)->setTime(10, 50)],
            [2, 'status_updated', 'document_request', 6,  'released document for Teresa Aquino',               $today->copy()->subDays(3)->setTime(11, 30)],
            [1, 'created',        'event',            1,  'created event: Barangay General Assembly',          $today->copy()->subDays(3)->setTime(13, 00)],
            [2, 'updated',        'event',            1,  'updated event details: Barangay General Assembly',  $today->copy()->subDays(3)->setTime(13, 45)],
            [3, 'status_updated', 'budget_transaction', 4, 'marked voucher #BV-2026-004 as paid',             $today->copy()->subDays(3)->setTime(14, 20)],
            [1, 'created',        'citizen',          11, 'registered citizen Mario Pascual',                  $today->copy()->subDays(3)->setTime(15, 00)],

            // ── 3 days ago ───────────────────────────────────────────
            [1, 'created',        'citizen',          12, 'registered citizen Gloria Santos',                  $today->copy()->subDays(2)->setTime(8, 20)],
            [2, 'created',        'document_request', 4,  'submitted document request for Mario Pascual',     $today->copy()->subDays(2)->setTime(9, 00)],
            [1, 'tags_updated',   'citizen',          10, 'updated tags for Teresa Aquino',                   $today->copy()->subDays(2)->setTime(9, 40)],
            [2, 'created',        'blotter',          4,  'filed blotter case #BL-2026-004 — disturbance',   $today->copy()->subDays(2)->setTime(10, 10)],
            [3, 'created',        'budget_transaction', 5, 'created voucher #BV-2026-005 for ₱12,750.00',   $today->copy()->subDays(2)->setTime(10, 55)],
            [2, 'action_added',   'blotter',          4,  'warning issued for blotter #BL-2026-004',         $today->copy()->subDays(2)->setTime(11, 35)],
            [1, 'created',        'event',            2,  'created event: Health & Wellness Day',             $today->copy()->subDays(2)->setTime(13, 00)],
            [2, 'created',        'document_request', 9,  'submitted document request for Gloria Santos',    $today->copy()->subDays(2)->setTime(14, 00)],
            [2, 'updated',        'document_request', 9,  'approved document request #DR-0009',              $today->copy()->subDays(2)->setTime(14, 35)],
            [3, 'status_updated', 'budget_transaction', 5, 'approved voucher #BV-2026-005',                 $today->copy()->subDays(2)->setTime(15, 10)],

            // ── Yesterday ────────────────────────────────────────────
            [1, 'created',        'citizen',          13, 'registered citizen Jose Fernandez',               $today->copy()->subDays(1)->setTime(8, 00)],
            [1, 'created',        'citizen',          14, 'registered citizen Evelyn Torres',                $today->copy()->subDays(1)->setTime(8, 40)],
            [2, 'created',        'document_request', 8,  'submitted document request for Jose Fernandez',  $today->copy()->subDays(1)->setTime(9, 15)],
            [2, 'status_updated', 'blotter',          2,  'updated status of blotter #BL-2026-002 to ongoing', $today->copy()->subDays(1)->setTime(9, 55)],
            [3, 'created',        'budget_transaction', 6, 'created voucher #BV-2026-006 for ₱30,000.00', $today->copy()->subDays(1)->setTime(10, 30)],
            [1, 'updated',        'citizen',          12, 'updated profile of Gloria Santos',               $today->copy()->subDays(1)->setTime(11, 00)],
            [2, 'created',        'blotter',          5,  'filed blotter case #BL-2026-005 — trespassing', $today->copy()->subDays(1)->setTime(11, 40)],
            [2, 'action_added',   'blotter',          5,  'summons issued for blotter #BL-2026-005',       $today->copy()->subDays(1)->setTime(12, 15)],
            [1, 'created',        'event',            3,  'created event: Youth Leadership Summit',        $today->copy()->subDays(1)->setTime(13, 20)],
            [3, 'status_updated', 'budget_transaction', 6, 'approved voucher #BV-2026-006',               $today->copy()->subDays(1)->setTime(14, 00)],
            [2, 'status_updated', 'document_request', 8,  'released document for Jose Fernandez',         $today->copy()->subDays(1)->setTime(14, 45)],
            [1, 'updated',        'citizen',          14, 'updated profile of Evelyn Torres',              $today->copy()->subDays(1)->setTime(15, 30)],
            [3, 'status_updated', 'budget_transaction', 6, 'marked voucher #BV-2026-006 as paid',        $today->copy()->subDays(1)->setTime(16, 00)],

            // ── Today ─────────────────────────────────────────────────
            [1, 'created',        'citizen',          15, 'registered citizen Ramon Dela Rosa',            $today->copy()->setTime(7, 55)],
            [1, 'created',        'citizen',          16, 'registered citizen Corazon Navarro',            $today->copy()->setTime(8, 20)],
            [2, 'created',        'document_request', 7,  'submitted document request for Ramon Dela Rosa', $today->copy()->setTime(8, 50)],
            [1, 'tags_updated',   'citizen',          15, 'updated tags for Ramon Dela Rosa',              $today->copy()->setTime(9, 05)],
            [2, 'updated',        'document_request', 7,  'approved document request #DR-0007',            $today->copy()->setTime(9, 30)],
            [3, 'created',        'budget_transaction', 7, 'created voucher #BV-2026-007 for ₱18,500.00', $today->copy()->setTime(9, 55)],
            [1, 'created',        'citizen',          17, 'registered citizen Rodrigo Castillo',           $today->copy()->setTime(10, 15)],
            [2, 'created',        'document_request', null, 'submitted document request for Rodrigo Castillo', $today->copy()->setTime(10, 40)],
            [3, 'status_updated', 'budget_transaction', 7, 'approved voucher #BV-2026-007',               $today->copy()->setTime(11, 00)],
            [2, 'status_updated', 'document_request', 7,  'released document for Ramon Dela Rosa',        $today->copy()->setTime(11, 25)],
            [1, 'updated',        'citizen',          16, 'updated profile of Corazon Navarro',            $today->copy()->setTime(13, 10)],
            [3, 'status_updated', 'budget_transaction', 7, 'marked voucher #BV-2026-007 as paid',        $today->copy()->setTime(13, 45)],
            [2, 'action_added',   'blotter',          5,  'mediation set for blotter #BL-2026-005',       $today->copy()->setTime(14, 00)],
            [1, 'exported',       'citizen',          null, 'exported citizens list to CSV',               $today->copy()->setTime(14, 30)],
            [2, 'created',        'event',            4,  'created event: Livelihood Training Program',   $today->copy()->setTime(15, 00)],
            [3, 'created',        'event',            5,  'created event: Senior Citizen Wellness Check', $today->copy()->setTime(15, 20)],
        ];

        $rows = array_map(fn($r) => [
            'user_id'      => $r[0],
            'action'       => $r[1],
            'subject_type' => $r[2],
            'subject_id'   => $r[3],
            'description'  => $r[4],
            'properties'   => null,
            'ip_address'   => '127.0.0.1',
            'created_at'   => $r[5],
        ], $logs);

        DB::table('eb_activity_logs')->insert($rows);

        $this->command->info('ActivityLogSeeder: inserted ' . count($rows) . ' activity log records.');
    }
}
