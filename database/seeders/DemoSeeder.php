<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // ============================================================
        // 1. USERS
        // ============================================================
        DB::table('users')->insertOrIgnore([
            ['id' => 1, 'name' => 'Admin Demo', 'email' => 'admin@demo.com', 'email_verified_at' => $now, 'password' => Hash::make('password'), 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Secretary Cruz', 'email' => 'secretary@demo.com', 'email_verified_at' => $now, 'password' => Hash::make('password'), 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'Treasurer Reyes', 'email' => 'treasurer@demo.com', 'email_verified_at' => $now, 'password' => Hash::make('password'), 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ============================================================
        // 2. LOOKUP TABLES
        // ============================================================

        // Civil Status
        DB::table('eb_civil')->insertOrIgnore([
            ['id' => 1, 'description' => 'Single'],
            ['id' => 2, 'description' => 'Married'],
            ['id' => 3, 'description' => 'Widowed'],
            ['id' => 4, 'description' => 'Separated'],
            ['id' => 5, 'description' => 'Annulled'],
            ['id' => 6, 'description' => 'Live-in'],
        ]);

        // Address / Zones (Puroks)
        DB::table('eb_address')->insertOrIgnore([
            ['id' => 1, 'description' => 'Purok 1'],
            ['id' => 2, 'description' => 'Purok 2'],
            ['id' => 3, 'description' => 'Purok 3'],
            ['id' => 4, 'description' => 'Purok 4'],
            ['id' => 5, 'description' => 'Purok 5'],
            ['id' => 6, 'description' => 'Purok 6'],
        ]);

        // Relations (family roles)
        DB::table('eb_relation')->insertOrIgnore([
            ['id' => 1, 'description' => 'Head'],
            ['id' => 2, 'description' => 'Spouse'],
            ['id' => 3, 'description' => 'Son'],
            ['id' => 4, 'description' => 'Daughter'],
            ['id' => 5, 'description' => 'Father'],
            ['id' => 6, 'description' => 'Mother'],
            ['id' => 7, 'description' => 'Brother'],
            ['id' => 8, 'description' => 'Sister'],
            ['id' => 9, 'description' => 'Grandfather'],
            ['id' => 10, 'description' => 'Grandmother'],
            ['id' => 11, 'description' => 'Grandchild'],
            ['id' => 12, 'description' => 'Uncle'],
            ['id' => 13, 'description' => 'Aunt'],
            ['id' => 14, 'description' => 'Nephew'],
            ['id' => 15, 'description' => 'Niece'],
            ['id' => 16, 'description' => 'Son-in-law'],
            ['id' => 17, 'description' => 'Daughter-in-law'],
            ['id' => 18, 'description' => 'Others'],
        ]);

        // Approval Status
        DB::table('eb_approval_status')->insertOrIgnore([
            ['id' => 1, 'description' => 'Pending'],
            ['id' => 2, 'description' => 'Approved'],
            ['id' => 3, 'description' => 'Rejected'],
        ]);

        // ============================================================
        // 3. SETTINGS
        // ============================================================
        DB::table('eb_settings')->updateOrInsert(['id' => 1], [
            'barangay_name'    => 'Barangay San Jose',
            'municipality'     => 'San Jose del Monte',
            'province'         => 'Bulacan',
            'address'          => 'Barangay Hall, Purok 1, Brgy. San Jose, San Jose del Monte, Bulacan',
            'captain_name'     => 'Hon. Roberto Santos',
            'captain_position' => 'Barangay Captain',
            'contact'          => '09171234567',
            'email'            => 'bsanjose@demo.gov.ph',
            'updated_at'       => $now,
        ]);

        // ============================================================
        // 4. TAGS
        // ============================================================
        DB::table('eb_tags')->insertOrIgnore([
            ['id' => 1, 'name' => 'Senior Citizen', 'color' => '#f59e0b', 'description' => '60 years old and above', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'PWD',            'color' => '#3b82f6', 'description' => 'Person with Disability',   'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => '4Ps',            'color' => '#8b5cf6', 'description' => 'Pantawid Pamilyang Pilipino Program', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'name' => 'Solo Parent',    'color' => '#ec4899', 'description' => 'Solo parent household',   'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'name' => 'Indigent',       'color' => '#ef4444', 'description' => 'Low-income household',    'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'name' => 'Out-of-School Youth', 'color' => '#06b6d4', 'description' => 'OSY',               'created_at' => $now, 'updated_at' => $now],
        ]);

        // ============================================================
        // 5. CITIZENS
        // ============================================================
        $citizens = [
            // id, fname, mname, lname, suffix, bday, gender(1=M,2=F), civil_status, address, voters, is_pwd, occupation, approval_status, isHead, relationId
            [1,  'Roberto',   'Cruz',      'Santos',    null,   '1965-03-12', 1, 2, 1, 1, 0, 'Barangay Captain',  2, 1, 1],
            [2,  'Maria',     'Dela',      'Santos',    null,   '1968-07-22', 2, 2, 1, 1, 0, 'Housewife',         2, 0, 2],
            [3,  'Jose',      'Cruz',      'Santos',    'Jr.',  '1995-11-05', 1, 1, 1, 1, 0, 'Teacher',           2, 0, 3],
            [4,  'Ana',       'Reyes',     'Santos',    null,   '1998-04-18', 2, 1, 1, 0, 0, 'Student',           2, 0, 4],
            [5,  'Pedro',     'Gomez',     'Reyes',     null,   '1955-08-30', 1, 2, 2, 1, 1, 'Retired',           2, 1, 1],
            [6,  'Rosario',   'Bautista',  'Reyes',     null,   '1958-12-14', 2, 2, 2, 1, 0, 'Housewife',         2, 0, 2],
            [7,  'Carlos',    'Mendoza',   'Reyes',     null,   '1985-06-20', 1, 2, 2, 1, 0, 'Engineer',          2, 0, 3],
            [8,  'Luisa',     'Torres',    'Reyes',     null,   '1990-02-28', 2, 2, 2, 1, 0, 'Nurse',             2, 0, 4],
            [9,  'Juan',      'Lim',       'Garcia',    null,   '1950-01-07', 1, 3, 3, 1, 0, 'Farmer',            2, 1, 1],
            [10, 'Elena',     'Cruz',      'Garcia',    null,   '1978-09-15', 2, 1, 3, 1, 0, 'Market Vendor',     2, 0, 4],
            [11, 'Miguel',    'Santos',    'Garcia',    null,   '2000-03-22', 1, 1, 3, 0, 0, 'Student',           2, 0, 3],
            [12, 'Sofia',     'Ramos',     'Dela Cruz', null,   '1972-11-30', 2, 2, 4, 1, 0, 'Teacher',           2, 1, 1],
            [13, 'Antonio',   'Flores',    'Dela Cruz', null,   '1970-05-17', 1, 2, 4, 1, 0, 'Driver',            2, 0, 2],
            [14, 'Carina',    'Villanueva','Dela Cruz', null,   '2001-08-08', 2, 1, 4, 0, 0, 'Student',           2, 0, 4],
            [15, 'Ramon',     'Pascual',   'Villanueva',null,   '1945-04-02', 1, 3, 5, 1, 1, 'Retired',           2, 1, 1],
            [16, 'Teresita',  'Aquino',    'Villanueva',null,   '1948-06-19', 2, 2, 5, 1, 1, 'Housewife',         2, 0, 2],
            [17, 'Emmanuel',  'Cruz',      'Villanueva',null,   '1975-12-03', 1, 2, 5, 1, 0, 'Carpenter',         2, 0, 3],
            [18, 'Marites',   'Lopez',     'Villanueva',null,   '1979-09-25', 2, 2, 5, 1, 0, 'Laundrywoman',      2, 0, 4],
            [19, 'Danilo',    'Hernandez', 'Mendoza',   null,   '1988-07-14', 1, 1, 6, 1, 0, 'Security Guard',    2, 1, 1],
            [20, 'Kristine',  'Navarro',   'Mendoza',   null,   '1992-03-08', 2, 1, 6, 1, 0, 'Call Center Agent', 2, 0, 2],
            [21, 'Francis',   'Aguilar',   'Bautista',  null,   '1963-10-21', 1, 2, 1, 1, 0, 'Fisherman',         2, 1, 1],
            [22, 'Natividad', 'Soriano',   'Bautista',  null,   '1966-02-14', 2, 2, 1, 1, 0, 'Housewife',         2, 0, 2],
            [23, 'Mark',      'De Leon',   'Bautista',  null,   '1993-05-30', 1, 1, 1, 1, 0, 'Delivery Rider',    2, 0, 3],
            [24, 'Jenny',     'Castillo',  'Bautista',  null,   '1996-11-12', 2, 1, 1, 1, 0, 'Cashier',           2, 0, 4],
            [25, 'Alfredo',   'Morales',   'Torres',    'Sr.',  '1940-08-05', 1, 3, 2, 1, 1, 'Retired',           2, 1, 1],
            [26, 'Erlinda',   'Abad',      'Torres',    null,   '1955-03-18', 2, 3, 2, 1, 0, 'Housewife',         2, 0, 2],
            [27, 'Jerome',    'Delos Reyes','Torres',   null,   '1982-06-27', 1, 2, 2, 1, 0, 'Mechanic',          2, 0, 3],
            [28, 'Christine', 'Padilla',   'Torres',    null,   '1985-09-10', 2, 2, 2, 1, 0, 'Beautician',        2, 0, 4],
            [29, 'Rodrigo',   'Cabrera',   'Lim',       null,   '1970-01-25', 1, 2, 3, 1, 0, 'Tricycle Driver',   2, 1, 1],
            [30, 'Gloria',    'Panganiban','Lim',       null,   '1973-07-30', 2, 2, 3, 1, 0, 'Sari-sari Store',   2, 0, 2],
        ];

        foreach ($citizens as $c) {
            DB::table('eb_citizen')->insertOrIgnore([
                'id'              => $c[0],
                'qrcode'          => 'QR-' . str_pad($c[0], 5, '0', STR_PAD_LEFT),
                'fname'           => $c[1],
                'mname'           => $c[2],
                'lname'           => $c[3],
                'suffix'          => $c[4],
                'bday'            => $c[5],
                'bplace'          => 'San Jose del Monte, Bulacan',
                'contact'         => '09' . rand(100000000, 999999999),
                'email'           => strtolower($c[1]) . '.' . strtolower($c[3]) . '@demo.com',
                'gender'          => $c[6],
                'civil_status'    => $c[7],
                'address'         => $c[8],
                'no'              => (string)rand(1, 99),
                'street'          => 'Sampaguita Street',
                'complete_address'=> 'Purok ' . $c[8] . ', Brgy. San Jose, San Jose del Monte, Bulacan',
                'voters'          => $c[9],
                'is_pwd'          => $c[10],
                'is_soloparents'  => 0,
                'citizenship'     => 'Filipino',
                'occupation'      => $c[11],
                'approval_status' => $c[12],
                'date_approved'   => $now,
                'user_id_approved'=> 1,
                'is_active'       => 1,
                'isHead'          => $c[13],
                'relationId'      => $c[14],
                'ic_fullname'     => 'Emergency Contact',
                'ic_contact'      => '09' . rand(100000000, 999999999),
                'ic_relationship' => 'Spouse',
                'date_created'    => $now,
                'date_last_updated' => $now,
            ]);
        }

        // Citizen Tags
        DB::table('eb_citizen_tag')->insertOrIgnore([
            ['citizen_id' => 5,  'tag_id' => 1], // Senior
            ['citizen_id' => 5,  'tag_id' => 2], // PWD
            ['citizen_id' => 9,  'tag_id' => 1], // Senior
            ['citizen_id' => 15, 'tag_id' => 1], // Senior
            ['citizen_id' => 15, 'tag_id' => 2], // PWD
            ['citizen_id' => 16, 'tag_id' => 1], // Senior
            ['citizen_id' => 16, 'tag_id' => 2], // PWD
            ['citizen_id' => 25, 'tag_id' => 1], // Senior
            ['citizen_id' => 25, 'tag_id' => 2], // PWD
            ['citizen_id' => 10, 'tag_id' => 3], // 4Ps
            ['citizen_id' => 10, 'tag_id' => 5], // Indigent
            ['citizen_id' => 12, 'tag_id' => 4], // Solo Parent
            ['citizen_id' => 19, 'tag_id' => 3], // 4Ps
            ['citizen_id' => 11, 'tag_id' => 6], // OSY
        ]);

        // ============================================================
        // 6. HOUSEHOLDS & FAMILIES
        // ============================================================
        $households = [
            [1, 1, 1, 5, 'Phase 1', '12', 'A', 'No. 12-A Sampaguita St., Purok 1, Brgy. San Jose'],
            [2, 2, 3, 5, 'Phase 2', '45', 'B', 'No. 45-B Rosal St., Purok 2, Brgy. San Jose'],
            [3, 3, 1, 9, 'Phase 1', '78', null, 'No. 78 Ilang-Ilang St., Purok 3, Brgy. San Jose'],
            [4, 4, 2, 12, 'Phase 3', '23', 'C', 'No. 23-C Malvar St., Purok 4, Brgy. San Jose'],
            [5, 5, 1, 15, 'Phase 2', '90', null, 'No. 90 Aguinaldo St., Purok 5, Brgy. San Jose'],
            [6, 6, 4, 19, 'Phase 4', '56', 'D', 'No. 56-D Bonifacio St., Purok 6, Brgy. San Jose'],
        ];

        foreach ($households as $h) {
            DB::table('eb_household')->insertOrIgnore([
                'id'             => $h[0],
                'addressId'      => $h[1],
                'blk'            => $h[2],
                'lot'            => null,
                'phaseStreet'    => $h[4],
                'no'             => $h[5],
                'internal'       => $h[6],
                'completeAdress' => $h[7],
                'citizenHeadId'  => $h[3],
                'user_id'        => 1,
                'date_created'   => $now,
                'date_lastupdated' => $now,
            ]);
        }

        // Families — citizen groups per household
        $families = [
            // household 1: Santos family (citizens 1-4)
            [1, 1, 1], [2, 1, 2], [3, 1, 3], [4, 1, 4],
            // household 2: Reyes family (citizens 5-8)
            [5, 2, 5], [6, 2, 6], [7, 2, 7], [8, 2, 8],
            // household 3: Garcia family (citizens 9-11)
            [7, 3, 9], [8, 3, 10], [9, 3, 11],
            // household 4: Dela Cruz family (citizens 12-14)
            [10, 4, 12], [11, 4, 13], [12, 4, 14],
            // household 5: Villanueva family (citizens 15-18)
            [13, 5, 15], [14, 5, 16], [15, 5, 17], [16, 5, 18],
            // household 6: Mendoza family (citizens 19-20)
            [17, 6, 19], [18, 6, 20],
        ];

        foreach ($families as $i => $f) {
            DB::table('eb_family')->insertOrIgnore([
                'id'               => $i + 1,
                'householdId'      => $f[1],
                'citizenId'        => $f[2],
                'date_created'     => $now,
                'date_last_updated'=> $now,
            ]);
        }

        // Update familyId on citizens
        $familyMap = [
            1=>1, 2=>1, 3=>1, 4=>1,
            5=>2, 6=>2, 7=>2, 8=>2,
            9=>3, 10=>3, 11=>3,
            12=>4, 13=>4, 14=>4,
            15=>5, 16=>5, 17=>5, 18=>5,
            19=>6, 20=>6,
        ];
        foreach ($familyMap as $citizenId => $familyId) {
            DB::table('eb_citizen')->where('id', $citizenId)->update(['familyId' => $familyId]);
        }

        // ============================================================
        // 7. BLOTTERS
        // ============================================================
        $blotters = [
            [
                'id' => 1, 'blotter_no' => 'BL-2026-001',
                'filed_date' => '2026-01-10', 'incident_date' => '2026-01-09', 'incident_time' => '22:30:00',
                'type' => 'disturbance', 'incident_location' => 'Purok 3, Brgy. San Jose',
                'complainant_name' => 'Juan Garcia', 'complainant_address' => 'Purok 3', 'complainant_contact' => '09171234567', 'complainant_citizen_id' => 9,
                'respondent_name'  => 'Unknown Neighbor', 'respondent_address' => 'Purok 3', 'respondent_contact' => null, 'respondent_citizen_id' => null,
                'narrative' => 'Complainant reported loud noise and videoke singing past midnight causing disturbance to the neighbors.',
                'attending_officer' => 'Brgy. Tanod Romeo Dela Cruz',
                'status' => 'settled', 'settled_date' => '2026-01-20',
                'action_taken' => 'Parties called for mediation. Respondent agreed to stop late night activities.',
                'remarks' => 'Amicably settled.',
            ],
            [
                'id' => 2, 'blotter_no' => 'BL-2026-002',
                'filed_date' => '2026-02-05', 'incident_date' => '2026-02-04', 'incident_time' => '14:00:00',
                'type' => 'dispute', 'incident_location' => 'Purok 1, Brgy. San Jose',
                'complainant_name' => 'Maria Santos', 'complainant_address' => 'Purok 1', 'complainant_contact' => '09181234567', 'complainant_citizen_id' => 2,
                'respondent_name'  => 'Natividad Bautista', 'respondent_address' => 'Purok 1', 'respondent_contact' => '09191234567', 'respondent_citizen_id' => 22,
                'narrative' => 'Dispute over property boundary fence between adjacent lots. Complainant claims respondent encroached 2 feet into her lot.',
                'attending_officer' => 'Brgy. Secretary Ana Lopez',
                'status' => 'ongoing',
                'action_taken' => 'First hearing conducted on Feb 10. Land titles requested for verification.',
                'remarks' => 'Second hearing scheduled for March 5, 2026.',
            ],
            [
                'id' => 3, 'blotter_no' => 'BL-2026-003',
                'filed_date' => '2026-02-28', 'incident_date' => '2026-02-27', 'incident_time' => '10:00:00',
                'type' => 'theft', 'incident_location' => 'Purok 2, Brgy. San Jose',
                'complainant_name' => 'Pedro Reyes', 'complainant_address' => 'Purok 2', 'complainant_contact' => '09201234567', 'complainant_citizen_id' => 5,
                'respondent_name'  => 'Unknown Person', 'respondent_address' => null, 'respondent_contact' => null, 'respondent_citizen_id' => null,
                'narrative' => 'Complainant reported that his motorcycle was stolen from his garage while he was sleeping. Estimated value: ₱45,000.',
                'attending_officer' => 'Brgy. Tanod Jose Reyes',
                'status' => 'referred', 'referred_to' => 'San Jose del Monte City Police Station',
                'action_taken' => 'Case referred to proper authorities due to criminal nature.',
                'remarks' => 'Police blotter filed. Case no. to be provided.',
            ],
            [
                'id' => 4, 'blotter_no' => 'BL-2026-004',
                'filed_date' => '2026-03-15', 'incident_date' => '2026-03-15', 'incident_time' => '08:30:00',
                'type' => 'physical_injury', 'incident_location' => 'Purok 4, Brgy. San Jose',
                'complainant_name' => 'Sofia Dela Cruz', 'complainant_address' => 'Purok 4', 'complainant_contact' => '09211234567', 'complainant_citizen_id' => 12,
                'respondent_name'  => 'Antonio Dela Cruz', 'respondent_address' => 'Purok 4', 'respondent_contact' => '09221234567', 'respondent_citizen_id' => 13,
                'narrative' => 'Complainant alleged that respondent, her husband, physically harmed her during a domestic altercation.',
                'attending_officer' => 'Brgy. Captain Roberto Santos',
                'status' => 'filed',
                'action_taken' => null,
                'remarks' => 'Referred to DSWD for intervention. Hearing scheduled.',
            ],
            [
                'id' => 5, 'blotter_no' => 'BL-2026-005',
                'filed_date' => '2026-04-20', 'incident_date' => '2026-04-19', 'incident_time' => '19:00:00',
                'type' => 'complaint', 'incident_location' => 'Purok 6, Brgy. San Jose',
                'complainant_name' => 'Danilo Mendoza', 'complainant_address' => 'Purok 6', 'complainant_contact' => '09231234567', 'complainant_citizen_id' => 19,
                'respondent_name'  => 'Mark Bautista', 'respondent_address' => 'Purok 1', 'respondent_contact' => '09241234567', 'respondent_citizen_id' => 23,
                'narrative' => 'Respondent allegedly owe complainant ₱15,000 as personal loan made in January 2026 with agreed repayment of 3 months.',
                'attending_officer' => 'Brgy. Secretary Ana Lopez',
                'status' => 'settled', 'settled_date' => '2026-05-10',
                'action_taken' => 'Parties agreed on a payment scheme. Respondent to pay ₱5,000 monthly.',
                'remarks' => 'Written agreement signed by both parties.',
            ],
        ];

        foreach ($blotters as $b) {
            DB::table('eb_blotters')->insertOrIgnore([
                'id'                    => $b['id'],
                'blotter_no'            => $b['blotter_no'],
                'filed_date'            => $b['filed_date'],
                'incident_date'         => $b['incident_date'],
                'incident_time'         => $b['incident_time'],
                'type'                  => $b['type'],
                'incident_location'     => $b['incident_location'],
                'complainant_name'      => $b['complainant_name'],
                'complainant_address'   => $b['complainant_address'],
                'complainant_contact'   => $b['complainant_contact'],
                'complainant_citizen_id'=> $b['complainant_citizen_id'],
                'respondent_name'       => $b['respondent_name'],
                'respondent_address'    => $b['respondent_address'],
                'respondent_contact'    => $b['respondent_contact'],
                'respondent_citizen_id' => $b['respondent_citizen_id'],
                'narrative'             => $b['narrative'],
                'attending_officer'     => $b['attending_officer'],
                'status'                => $b['status'],
                'settled_date'          => $b['settled_date'] ?? null,
                'referred_to'           => $b['referred_to'] ?? null,
                'action_taken'          => $b['action_taken'],
                'remarks'               => $b['remarks'],
                'created_by'            => 1,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);
        }

        // Blotter parties
        $parties = [
            [1, 1, 'complainant', 9,  'Juan Garcia',       'Purok 3', '09171234567'],
            [2, 1, 'respondent',  null,'Unknown Neighbor',  'Purok 3', null],
            [3, 2, 'complainant', 2,  'Maria Santos',       'Purok 1', '09181234567'],
            [4, 2, 'respondent',  22, 'Natividad Bautista', 'Purok 1', '09191234567'],
            [5, 3, 'complainant', 5,  'Pedro Reyes',        'Purok 2', '09201234567'],
            [6, 4, 'complainant', 12, 'Sofia Dela Cruz',    'Purok 4', '09211234567'],
            [7, 4, 'respondent',  13, 'Antonio Dela Cruz',  'Purok 4', '09221234567'],
            [8, 5, 'complainant', 19, 'Danilo Mendoza',     'Purok 6', '09231234567'],
            [9, 5, 'respondent',  23, 'Mark Bautista',      'Purok 1', '09241234567'],
        ];

        foreach ($parties as $p) {
            DB::table('eb_blotter_parties')->insertOrIgnore([
                'id'         => $p[0],
                'blotter_id' => $p[1],
                'role'       => $p[2],
                'citizen_id' => $p[3],
                'name'       => $p[4],
                'address'    => $p[5],
                'contact'    => $p[6],
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ============================================================
        // 8. FISCAL YEAR & BUDGET
        // ============================================================
        DB::table('eb_fiscal_years')->insertOrIgnore([
            'id' => 1, 'year' => 2026, 'label' => 'Fiscal Year 2026',
            'is_active' => 1, 'beginning_cash_balance' => 500000.00,
            'notes' => 'Annual Budget FY 2026', 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Budget Programs
        $programs = [
            [1, 'General Government Services',  null,         1],
            [2, 'Health & Nutrition Services',  null,         2],
            [3, 'Peace & Order',                null,         3],
            [4, 'Social Services',              null,         4],
            [5, 'Development Fund (20%)',        'dev_fund',   5],
            [6, 'SK Fund (10%)',                 'sk_fund',    6],
            [7, 'Calamity / LDRRM Fund (5%)',   'calamity',   7],
            [8, 'GAD Fund (5%)',                 'gad',        8],
        ];

        foreach ($programs as $p) {
            DB::table('eb_budget_programs')->insertOrIgnore([
                'id' => $p[0], 'name' => $p[1], 'special_fund' => $p[2],
                'sort_order' => $p[3], 'is_active' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Budget Allocations
        $allocations = [
            [1, 1, 1, 'PS',   380000.00],
            [2, 1, 1, 'MOOE', 150000.00],
            [3, 1, 2, 'MOOE', 120000.00],
            [4, 1, 3, 'MOOE',  80000.00],
            [5, 1, 4, 'MOOE', 100000.00],
            [6, 1, 5, 'CO',   200000.00],
            [7, 1, 7, 'MOOE',  50000.00],
            [8, 1, 8, 'MOOE',  50000.00],
        ];

        foreach ($allocations as $a) {
            DB::table('eb_budget_allocations')->insertOrIgnore([
                'id' => $a[0], 'fiscal_year_id' => $a[1], 'program_id' => $a[2],
                'object_class' => $a[3], 'appropriation' => $a[4],
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Budget Line Items
        $lineItems = [
            [1, 1, 1, 'PS',   '701', 'Salaries & Wages',              180000.00, 1],
            [2, 1, 1, 'PS',   '702', 'PERA',                           60000.00, 2],
            [3, 1, 1, 'PS',   '703', 'Other Compensation',             80000.00, 3],
            [4, 1, 1, 'PS',   '704', 'Honoraria',                      60000.00, 4],
            [5, 1, 1, 'MOOE', '801', 'Travelling Expenses',            20000.00, 1],
            [6, 1, 1, 'MOOE', '802', 'Office Supplies',                30000.00, 2],
            [7, 1, 1, 'MOOE', '803', 'Utility Expenses',               50000.00, 3],
            [8, 1, 1, 'MOOE', '804', 'Communication Expenses',         10000.00, 4],
            [9, 1, 1, 'MOOE', '805', 'Repairs & Maintenance',          40000.00, 5],
            [10,1, 2, 'MOOE', '806', 'Medical Supplies',               60000.00, 1],
            [11,1, 2, 'MOOE', '807', 'Health Programs',                60000.00, 2],
            [12,1, 3, 'MOOE', '808', 'Peace & Order Programs',         80000.00, 1],
            [13,1, 4, 'MOOE', '809', 'Social Services Programs',      100000.00, 1],
            [14,1, 5, 'CO',   '901', 'Infrastructure Projects',       200000.00, 1],
        ];

        foreach ($lineItems as $li) {
            DB::table('eb_budget_line_items')->insertOrIgnore([
                'id' => $li[0], 'fiscal_year_id' => $li[1], 'program_id' => $li[2],
                'object_class' => $li[3], 'object_code' => $li[4], 'name' => $li[5],
                'appropriation' => $li[6], 'sort_order' => $li[7],
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Budget Suppliers
        $suppliers = [
            [1, 'ABC Office Supplies',       '123-456-789-000', 'San Jose del Monte, Bulacan', 'individual', 'vat',     'goods',    'Juan Mercado',    '09111111111'],
            [2, 'XYZ Construction Corp',     '987-654-321-000', 'Malolos, Bulacan',            'corporation','vat',     'services', 'Pedro Builders',  '09222222222'],
            [3, 'Dela Cruz Medical Supply',  '456-789-123-000', 'Caloocan City',               'corporation','vat',     'goods',    'Ana Cruz',        '09333333333'],
            [4, 'Luz Catering Services',     '321-123-456-000', 'San Jose del Monte, Bulacan', 'individual', 'non_vat', 'services', 'Luz Reyes',       '09444444444'],
            [5, 'Malaya Hardware',           '654-321-987-000', 'Guiguinto, Bulacan',          'corporation','vat',     'goods',    'Tony Malaya',     '09555555555'],
        ];

        foreach ($suppliers as $s) {
            DB::table('eb_budget_suppliers')->insertOrIgnore([
                'id' => $s[0], 'name' => $s[1], 'tin' => $s[2], 'address' => $s[3],
                'business_type' => $s[4], 'vat_type' => $s[5], 'provides' => $s[6],
                'contact_person' => $s[7], 'contact_no' => $s[8], 'is_active' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Budget Transactions (DVs, PCVs)
        $transactions = [
            [1, 1, 'DV',  'DV-2026-001', 'paid',      'General Fund', 15000.00, 1, '2026-01-15', 'ABC Office Supplies',      '123-456-789-000', 'check', 'CHK-001', '2026-01-15', 'PNB', 'OR-001', '2026-01-15', 'Purchase of office supplies for Q1 2026', 1, 6],
            [2, 1, 'DV',  'DV-2026-002', 'paid',      'General Fund', 45000.00, 2, '2026-02-10', 'XYZ Construction Corp',    '987-654-321-000', 'check', 'CHK-002', '2026-02-10', 'BDO', 'OR-002', '2026-02-10', 'Labor & materials for drainage repair, Purok 3', 1, 9],
            [3, 1, 'DV',  'DV-2026-003', 'approved',  'Health Fund',  28500.00, 3, '2026-03-05', 'Dela Cruz Medical Supply',  '456-789-123-000', 'cash',  null,      null,         null,  null,    null,         'Purchase of medicines & medical supplies, Q1', 1, 10],
            [4, 1, 'PCV', 'PCV-2026-001','paid',       'General Fund',  3200.00, null,'2026-01-20','Miscellaneous Expenses',   null,              'cash',  null,      null,         null,  null,    null,         'Petty cash for office miscellaneous expenses', 1, 8],
            [5, 1, 'PCV', 'PCV-2026-002','paid',       'General Fund',  4800.00, 4, '2026-02-25', 'Luz Catering Services',    '321-123-456-000', 'cash',  null,      null,         null,  null,    null,         'Catering for Barangay Assembly Feb 2026', 1, 13],
            [6, 1, 'DV',  'DV-2026-004', 'draft',     'General Fund', 120000.00, 5, '2026-04-01','Malaya Hardware',           '654-321-987-000', 'check', 'CHK-003', '2026-04-01', 'PNB', null,    null,         'Construction materials for covered court improvement', 1, 14],
            [7, 1, 'DV',  'DV-2026-005', 'paid',      'General Fund',  38000.00, null,'2026-03-25','Various Payees',          null,              'cash',  null,      null,         null,  'OR-003','2026-03-25', 'Social services assistance for indigent families, Q1', 1, 13],
        ];

        foreach ($transactions as $t) {
            DB::table('eb_budget_transactions')->insertOrIgnore([
                'id'             => $t[0],
                'fiscal_year_id' => $t[1],
                'voucher_type'   => $t[2],
                'voucher_no'     => $t[3],
                'status'         => $t[4],
                'fund_cluster'   => $t[5],
                'amount'         => $t[6],
                'supplier_id'    => $t[7],
                'transaction_date'=> $t[8],
                'payee'          => $t[9],
                'payee_tin'      => $t[10],
                'mode_of_payment'=> $t[11],
                'check_no'       => $t[12],
                'check_date'     => $t[13],
                'bank_name'      => $t[14],
                'or_number'      => $t[15],
                'or_date'        => $t[16],
                'description'    => $t[17],
                'recorded_by'    => $t[18],
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        // Transaction Lines
        $txLines = [
            [1, 1, 6,  15000.00, 'Office supplies Q1'],
            [2, 2, 9,  45000.00, 'Drainage repair materials & labor'],
            [3, 3, 10, 28500.00, 'Medical supplies Q1'],
            [4, 4, 8,   3200.00, 'Miscellaneous petty cash'],
            [5, 5, 8,   4800.00, 'Catering assembly'],
            [6, 6, 14,120000.00, 'Construction materials covered court'],
            [7, 7, 13, 38000.00, 'Social services assistance'],
        ];

        foreach ($txLines as $tl) {
            DB::table('eb_budget_transaction_lines')->insertOrIgnore([
                'id'             => $tl[0],
                'transaction_id' => $tl[1],
                'line_item_id'   => $tl[2],
                'amount'         => $tl[3],
                'description'    => $tl[4],
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        // ============================================================
        // 9. INVENTORY
        // ============================================================
        $categories = [
            [1, 'Office Supplies',    'office-supplies',    'Pens, papers, folders, etc.',       'mgc_edit_line',   'primary'],
            [2, 'Medical Supplies',   'medical-supplies',   'Medicines, first aid, PPE',          'mgc_heart_line',  'danger'],
            [3, 'Equipment',          'equipment',          'Computers, printers, tools',         'mgc_settings_line','info'],
            [4, 'Cleaning Materials', 'cleaning-materials', 'Brooms, mops, disinfectants',        'mgc_box_3_line',  'success'],
            [5, 'Relief Goods',       'relief-goods',       'Rice, canned goods, relief packs',   'mgc_box_line',    'warning'],
        ];

        foreach ($categories as $c) {
            DB::table('eb_inventory_categories')->insertOrIgnore([
                'id' => $c[0], 'name' => $c[1], 'slug' => $c[2], 'description' => $c[3],
                'icon' => $c[4], 'color' => $c[5], 'is_active' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $items = [
            [1, 1, 'Bond Paper (Ream)',     'SKU-001', 'pcs',  45, 10, 'A4 size bond paper'],
            [2, 1, 'Ballpen (Box)',         'SKU-002', 'box',  18,  5, 'Black ballpen, 12 pcs/box'],
            [3, 1, 'Folder (Long)',         'SKU-003', 'pcs',  80, 20, 'Long brown folder'],
            [4, 2, 'Paracetamol 500mg',     'SKU-004', 'tabs',250, 50, 'Pain reliever tablets'],
            [5, 2, 'Amoxicillin 500mg',     'SKU-005', 'caps',150, 30, 'Antibiotic capsules'],
            [6, 2, 'Surgical Mask',         'SKU-006', 'pcs', 300,100, 'Disposable 3-ply mask'],
            [7, 3, 'Desktop Computer',      'SKU-007', 'unit',  2,  1, 'Office desktop computer'],
            [8, 3, 'Printer',               'SKU-008', 'unit',  1,  1, 'Inkjet printer'],
            [9, 4, 'Broom (Walis Tingting)','SKU-009', 'pcs',  10,  3, 'Native broom'],
            [10,5, 'Rice (50kg sack)',       'SKU-010', 'sack', 25,  5, 'Well-milled rice for relief'],
            [11,5, 'Sardines (canned)',      'SKU-011', 'pcs', 180, 30, '155g canned sardines'],
        ];

        foreach ($items as $i) {
            DB::table('eb_inventory_items')->insertOrIgnore([
                'id' => $i[0], 'category_id' => $i[1], 'name' => $i[2], 'sku' => $i[3],
                'unit' => $i[4], 'stock' => $i[5], 'low_stock_threshold' => $i[6],
                'description' => $i[7], 'is_active' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Stock ins
        $stockIns = [
            [1, 1,  50, 0,  'Purchase', 'DV-2026-001', null],
            [2, 2,  24, 0,  'Purchase', 'DV-2026-001', null],
            [3, 3, 100, 0,  'Purchase', 'DV-2026-001', null],
            [4, 4, 300, 0,  'Purchase', 'DV-2026-003', '2026-12-31'],
            [5, 5, 200, 0,  'Purchase', 'DV-2026-003', '2026-09-30'],
            [6, 6, 500, 0,  'Purchase', 'DV-2026-003', null],
            [7, 10, 30, 0,  'Donation', 'REF-001',     null],
            [8, 11,200, 0,  'Donation', 'REF-001',     null],
        ];

        foreach ($stockIns as $i => $s) {
            DB::table('eb_inventory_stock_ins')->insertOrIgnore([
                'id' => $i + 1, 'item_id' => $s[0], 'quantity' => $s[1],
                'quantity_before' => $s[2], 'source' => $s[3], 'reference' => $s[4],
                'expiry_date' => $s[5], 'created_by' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // ============================================================
        // 10. EVENTS & ATTENDANCE
        // ============================================================
        $events = [
            [1, 'Barangay Assembly Q1 2026',     'Quarterly meeting for all residents of Brgy. San Jose.',  'Assembly',  'Barangay Hall',          '2026-02-28', '08:00:00', '12:00:00', 'closed', 1],
            [2, 'Health & Wellness Day',          'Free medical check-up, dental, and nutrition counseling.','Health',   'Barangay Health Center', '2026-03-20', '07:00:00', '16:00:00', 'closed', 1],
            [3, 'Clean & Green Drive',            'Community clean-up and tree planting activity.',          'Environment','Brgy. San Jose Streets',  '2026-04-22', '06:00:00', '10:00:00', 'closed', 1],
            [4, 'Livelihood Seminar',             'Seminar on small business and livelihood opportunities.', 'Livelihood','Barangay Hall',          '2026-05-15', '09:00:00', '15:00:00', 'closed', 1],
            [5, 'Barangay Fiesta 2026',           'Annual fiesta celebration with activities and raffle.',   'Fiesta',    'Covered Court',          '2026-06-15', '10:00:00', '22:00:00', 'open',   1],
        ];

        foreach ($events as $e) {
            DB::table('eb_event')->insertOrIgnore([
                'id' => $e[0], 'title' => $e[1], 'description' => $e[2], 'category' => $e[3],
                'venue' => $e[4], 'event_date' => $e[5], 'event_start' => $e[6], 'event_end' => $e[7],
                'raffle_enabled' => $e[0] == 5 ? 1 : 0, 'allow_winner_repeat' => 0,
                'status' => $e[8], 'user_id' => 1,
                'date_created' => $now, 'date_updated' => $now,
            ]);
        }

        // Attendance — who attended each event
        $attendanceData = [
            1 => [1, 2, 5, 6, 9, 12, 15, 16, 19, 20, 21, 22, 25, 26, 29, 30],
            2 => [2, 5, 6, 9, 10, 12, 15, 16, 21, 22, 25, 26, 29, 30],
            3 => [1, 3, 7, 11, 13, 17, 19, 20, 23, 24, 27, 28],
            4 => [2, 6, 8, 10, 12, 18, 20, 22, 24, 26, 28, 30],
            5 => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 19, 20, 21, 22, 23, 24, 25, 26],
        ];

        $attId = 1;
        foreach ($attendanceData as $eventId => $citizenIds) {
            foreach ($citizenIds as $citizenId) {
                DB::table('eb_event_attendance')->insertOrIgnore([
                    'id'        => $attId++,
                    'eventId'   => $eventId,
                    'citizenId' => $citizenId,
                    'time_in'   => Carbon::parse($events[$eventId - 1][5] . ' ' . $events[$eventId - 1][6])->addMinutes(rand(0, 60)),
                    'method'    => 'manual',
                ]);
            }
        }

        // Raffle winner (Fiesta event)
        DB::table('eb_event_winner')->insertOrIgnore([
            ['id' => 1, 'eventId' => 5, 'citizenId' => 3,  'round' => 1, 'prize_label' => '3rd Prize — Electric Fan',    'drawn_at' => $now],
            ['id' => 2, 'eventId' => 5, 'citizenId' => 19, 'round' => 2, 'prize_label' => '2nd Prize — Grocery Basket',  'drawn_at' => $now],
            ['id' => 3, 'eventId' => 5, 'citizenId' => 7,  'round' => 3, 'prize_label' => '1st Prize — Smart TV 32"',    'drawn_at' => $now],
        ]);

        // ============================================================
        // 11. DOCUMENT TYPES & REQUESTS
        // ============================================================
        $docTypes = [
            [1, 'Barangay Clearance',       'BC',  'Official clearance for employment and other purposes.',  0, 50.00,  1, 1],
            [2, 'Certificate of Residency',  'CR',  'Certifies that the person is a resident of the barangay.', 0, 30.00, 1, 2],
            [3, 'Certificate of Indigency',  'CI',  'Certifies that the person belongs to an indigent family.', 0,  0.00, 1, 3],
            [4, 'Business Clearance',        'BIC', 'Clearance for business operations within the barangay.',  0, 100.00, 1, 4],
            [5, 'Certificate of Good Moral', 'CGMC','Certifies good moral character of the resident.',         0, 30.00,  1, 5],
        ];

        foreach ($docTypes as $dt) {
            DB::table('eb_document_types')->insertOrIgnore([
                'id' => $dt[0], 'name' => $dt[1], 'short_name' => $dt[2],
                'description' => $dt[3], 'is_paid' => $dt[4], 'fee' => $dt[5],
                'requires_approval' => 1, 'is_active' => 1, 'sort_order' => $dt[7],
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $docRequests = [
            [1, 1, 1,  'released', 50.00, 1, 'OR-2026-0001', '2026-01-20', 'For employment purposes'],
            [2, 1, 3,  'released', 50.00, 1, 'OR-2026-0002', '2026-01-25', 'For employment purposes'],
            [3, 2, 5,  'released', 30.00, 1, 'OR-2026-0003', '2026-02-05', 'For school enrollment'],
            [4, 3, 10, 'released',  0.00, 1, null,           '2026-02-12', 'For medical assistance'],
            [5, 1, 7,  'released', 50.00, 1, 'OR-2026-0004', '2026-02-20', 'For bank loan requirements'],
            [6, 2, 12, 'approved', 30.00, 0, null,            null,         'For senior citizen ID application'],
            [7, 5, 15, 'pending',  30.00, 0, null,            null,         'For character reference'],
            [8, 4, 21, 'pending', 100.00, 0, null,            null,         'For sari-sari store permit'],
            [9, 3, 25, 'released',  0.00, 1, null,           '2026-03-10', 'For DSWD assistance'],
            [10,1, 23, 'released', 50.00, 1, 'OR-2026-0005', '2026-03-15', 'For employment purposes'],
        ];

        foreach ($docRequests as $dr) {
            DB::table('eb_document_requests')->insertOrIgnore([
                'id'               => $dr[0],
                'document_type_id' => $dr[1],
                'citizen_id'       => $dr[2],
                'status'           => $dr[3],
                'fee'              => $dr[4],
                'fee_paid'         => $dr[5],
                'or_number'        => $dr[6],
                'released_at'      => $dr[7] ? Carbon::parse($dr[7]) : null,
                'approved_by'      => in_array($dr[3], ['approved','released']) ? 1 : null,
                'approved_at'      => in_array($dr[3], ['approved','released']) ? $now : null,
                'custom_fields'    => json_encode(['purpose' => $dr[8]]),
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }

        $this->command->info('✅ Demo data seeded successfully for practice_e-demo!');
        $this->command->info('   Users: admin@demo.com / secretary@demo.com / treasurer@demo.com (password: password)');
        $this->command->info('   Citizens: 30 residents across 6 puroks');
        $this->command->info('   Households: 6 | Blotters: 5 | Events: 5 | Budget DVs: 7 | Documents: 10');
    }
}
