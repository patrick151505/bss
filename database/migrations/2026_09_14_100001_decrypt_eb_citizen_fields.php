<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Store the citizen PII fields as plaintext.
     *
     * These arrived encrypted from the production dump, but the app never
     * encrypted on write — only on read — so the data was already drifting
     * towards a mix of both. Decrypting in place makes the database
     * readable and consistent with what the app writes.
     *
     * Values that are already plaintext are left alone, so this is safe to
     * re-run.
     */
    private const FIELDS = [
        'mname', 'bplace', 'contact', 'email', 'complete_address',
        'ic_email', 'ic_fullname', 'ic_contact', 'ic_address', 'ic_relationship',
    ];

    public function up(): void
    {
        $updated = 0;
        $skipped = 0;

        DB::table('eb_citizen')
            ->select(array_merge(['id'], self::FIELDS))
            ->orderBy('id')
            ->chunk(1000, function ($rows) use (&$updated, &$skipped) {
                foreach ($rows as $row) {
                    $changes = [];

                    foreach (self::FIELDS as $field) {
                        $value = $row->$field;

                        if ($value === null || $value === '') {
                            continue;
                        }

                        try {
                            $plain = Crypt::decrypt($value);
                        } catch (\Throwable) {
                            // Already plaintext, or ciphertext that was
                            // truncated on write and cannot be recovered.
                            $skipped++;
                            continue;
                        }

                        // A value may decrypt to null (an encrypted empty
                        // string); that still has to be written back, or the
                        // ciphertext stays in the column.
                        if ($plain !== $value) {
                            $changes[$field] = $plain;
                        }
                    }

                    if ($changes) {
                        DB::table('eb_citizen')->where('id', $row->id)->update($changes);
                        $updated++;
                    }
                }
            });

        echo "Decrypted {$updated} citizen rows ({$skipped} values left as-is).\n";
    }

    /**
     * Not reversible: re-encrypting would produce different ciphertext than
     * the original (Laravel uses a random IV per value), so rolling back
     * would not restore the previous bytes. Restore from a dump instead.
     */
    public function down(): void
    {
        // Intentionally a no-op.
    }
};
