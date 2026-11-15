<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A handful of approved citizens so the modules that need a person to point
 * at — releases, rentals, document requests — are usable on a fresh install.
 * Idempotent: re-running updates the same records rather than duplicating.
 */
class DemoCitizenSeeder extends Seeder
{
    /** [fname, mname, lname, gender(1=M,2=F), bday, civil_status, contact, occupation, street] */
    private const PEOPLE = [
        ['Juan',     'Santos',   'Dela Cruz', 1, '1985-03-12', 2, '09171234567', 'Tricycle Driver',   'Rizal Street'],
        ['Maria',    'Reyes',    'Santos',    2, '1990-07-25', 2, '09182345678', 'Teacher',           'Mabini Street'],
        ['Pedro',    'Garcia',   'Ramos',     1, '1978-11-03', 2, '09193456789', 'Carpenter',         'Bonifacio Street'],
        ['Ana',      'Lopez',    'Mendoza',   2, '1996-01-18', 1, '09204567890', 'Sari-sari Store',   'Del Pilar Street'],
        ['Ricardo',  'Bautista', 'Villanueva',1, '1965-09-30', 3, '09215678901', 'Retired',           'Luna Street'],
        ['Josefina', 'Cruz',     'Aquino',    2, '2001-05-22', 1, '09226789012', 'Student',           'Aguinaldo Street'],
    ];

    public function run(): void
    {
        $now     = now();
        $created = 0;
        $updated = 0;

        foreach (self::PEOPLE as $i => [$fname, $mname, $lname, $gender, $bday, $civil, $contact, $occupation, $street]) {
            $existing = DB::table('eb_citizen')
                ->where('fname', $fname)
                ->where('lname', $lname)
                ->first();

            $address = trim("{$street}, Barangay Poblacion Tres, City of Cabuyao, Laguna");

            $row = [
                'fname'             => $fname,
                'mname'             => $mname,
                'lname'             => $lname,
                'gender'            => $gender,
                'bday'              => $bday,
                'bplace'            => 'City of Cabuyao, Laguna',
                'civil_status'      => $civil,
                'contact'           => $contact,
                'occupation'        => $occupation,
                'citizenship'       => 'Filipino',
                'street'            => $street,
                'complete_address'  => $address,
                'owner_status'      => 1,
                'is_pwd'            => 0,
                'is_soloparents'    => 0,
                'voters'            => $i < 4 ? 1 : 0,
                'approval_status'   => 2, // Approved
                'is_active'         => 1,
                'date_last_updated' => $now,
            ];

            if ($existing) {
                DB::table('eb_citizen')->where('id', $existing->id)->update($row);
                $updated++;
                continue;
            }

            DB::table('eb_citizen')->insert($row + [
                // Matches how CitizenController mints a QR token.
                'qrcode'        => md5(Str::random(12) . $fname . $lname),
                'date_created'  => $now,
                'date_approved' => $now,
            ]);
            $created++;
        }

        $this->command?->info("Demo citizens — created: {$created}, updated: {$updated}.");
    }
}
