<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Bootstraps a fresh barangay (SaaS tenant) database:
 *   1. Permissions + roles (incl. Super Admin)
 *   2. Lookup defaults (civil status, approval status, puroks)
 *   3. The barangay's admin login, assigned Super Admin
 *
 * Admin credentials can be passed via env so each tenant gets its own:
 *   SEED_ADMIN_NAME, SEED_ADMIN_EMAIL, SEED_ADMIN_PASSWORD
 * (sensible defaults are used if not provided).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles + permissions
        $this->call(PermissionSeeder::class);

        // 2. Lookup tables every barangay needs (dropdowns work out of the box)
        $this->call(BarangayLookupSeeder::class);

        // 3. Admin account for this tenant
        $name     = env('SEED_ADMIN_NAME', 'Administrator');
        $email    = env('SEED_ADMIN_EMAIL', 'admin@demo.com');
        $password = env('SEED_ADMIN_PASSWORD', 'password'); // change on first login

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => Hash::make($password),
                'email_verified_at' => now(),
                'remember_token'    => Str::random(10),
                'is_active'         => 1,
            ]
        );

        $admin->assignRole('Super Admin');

        $this->command?->info("Admin ready → {$email} (Super Admin). Change the password on first login.");
    }
}
