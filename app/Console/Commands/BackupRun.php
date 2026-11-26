<?php

namespace App\Console\Commands;

use App\Support\BackupRunner;
use Illuminate\Console\Command;

/** Weekly (and on-demand) backup: database + uploads → encrypted ZIP → Google Drive. */
class BackupRun extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Back up the database and uploads (encrypted ZIP) and copy it to Google Drive';

    public function handle(BackupRunner $runner): int
    {
        $this->info('Backing up…');
        $b = $runner->run();

        if ($b->status === 'success') {
            $this->info("Done: {$b->filename} (" . $b->sizeLabel() . ", {$b->files_count} upload files, Drive: {$b->drive_status})");
            if ($b->message) $this->warn($b->message);
            return self::SUCCESS;
        }
        $this->error('Backup failed: ' . $b->message);
        return self::FAILURE;
    }
}
