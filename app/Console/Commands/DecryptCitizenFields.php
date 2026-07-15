<?php

namespace App\Console\Commands;

use App\Models\Citizen;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class DecryptCitizenFields extends Command
{
    protected $signature = 'citizen:decrypt
                            {--dry-run : Preview what will be decrypted without saving}
                            {--id= : Decrypt a single citizen by ID}';

    protected $description = 'Decrypt encrypted fields (mname, bplace, ic_*) in eb_citizen and store plain text';

    protected array $encryptedFields = [
        'mname',
        'bplace',
        'contact',
        'email',
        'complete_address',
        'ic_email',
        'ic_fullname',
        'ic_contact',
        'ic_address',
        'ic_relationship',
    ];

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $singleId = $this->option('id');

        if ($isDryRun) {
            $this->warn('DRY RUN — no changes will be saved.');
        }

        $query = Citizen::query();

        if ($singleId) {
            $query->where('id', $singleId);
        }

        $total   = $query->count();
        $updated = 0;
        $skipped = 0;

        $this->info("Processing {$total} citizen record(s)...");
        $this->newLine();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('id')->chunk(100, function ($citizens) use (
            $isDryRun, &$updated, &$skipped, $bar
        ) {
            foreach ($citizens as $citizen) {
                $changes = [];

                foreach ($this->encryptedFields as $field) {
                    $raw = $citizen->getRawOriginal($field);

                    if (empty($raw)) {
                        continue;
                    }

                    try {
                        $plain = Crypt::decrypt($raw);
                        $changes[$field] = $plain;
                    } catch (DecryptException) {
                        // Already plain text or different encryption — leave as-is
                        $skipped++;
                    }
                }

                if (!empty($changes)) {
                    if (!$isDryRun) {
                        Citizen::where('id', $citizen->id)->update($changes);
                    } else {
                        $this->newLine();
                        $this->line("  [ID {$citizen->id}] {$citizen->lname}, {$citizen->fname}");
                        foreach ($changes as $field => $plain) {
                            $this->line("    <fg=yellow>{$field}</> → <fg=green>{$plain}</>");
                        }
                    }
                    $updated++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total records',          $total],
                ['Records with changes',   $updated],
                ['Fields already plain',   $skipped],
            ]
        );

        if ($isDryRun) {
            $this->warn('Dry run complete. Run without --dry-run to apply changes.');
        } else {
            $this->info('Decryption complete. All encrypted fields are now stored as plain text.');
        }

        return self::SUCCESS;
    }
}
