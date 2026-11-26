<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use ZipArchive;

/**
 * Makes one backup: the whole database (mysqldump) plus the uploads folder
 * (storage/app/public) in one AES-256 password-protected ZIP, then copies it
 * to Google Drive and removes backups older than `backup.keep_days`.
 *
 * The ZIP opens with 7-Zip (or WinZip) using BACKUP_PASSWORD:
 *   database.sql   — import into MySQL to restore
 *   uploads/…      — copy back into storage/app/public
 */
class BackupRunner
{
    /** Problems that stop a backup from running at all (shown on the Backups page). */
    public static function setupProblems(): array
    {
        $p = [];
        if (!filled(config('backup.password'))) {
            $p[] = 'BACKUP_PASSWORD is not set in the server .env, so backups cannot be password-protected.';
        }
        if (!class_exists(ZipArchive::class) || !defined('ZipArchive::EM_AES_256')) {
            $p[] = 'The PHP zip extension with AES encryption is not available on this server.';
        }
        if (!self::mysqldumpWorks()) {
            $p[] = 'The database export tool (mysqldump) was not found. Set BACKUP_MYSQLDUMP in .env to its full path.';
        }
        return $p;
    }

    public static function mysqldumpWorks(): bool
    {
        $out = [];
        @exec(escapeshellarg(config('backup.mysqldump')) . ' --version 2>&1', $out, $code);
        return $code === 0;
    }

    public function run(?int $userId = null): Backup
    {
        @set_time_limit(0);
        $backup = Backup::create(['status' => 'running', 'triggered_by' => $userId, 'started_at' => now()]);
        $tmpSql = null;

        try {
            if ($problems = self::setupProblems()) {
                throw new RuntimeException(implode(' ', $problems));
            }

            $dir = config('backup.path');
            if (!is_dir($dir)) mkdir($dir, 0750, true);
            // Keep the folder private even if the web server ever pointed at storage/.
            if (!is_file($dir . '/.htaccess')) file_put_contents($dir . '/.htaccess', "Require all denied\n");

            $name   = 'bss-backup-' . now()->format('Y-m-d-His') . '.zip';
            $tmpSql = $dir . DIRECTORY_SEPARATOR . 'database-' . uniqid() . '.sql';
            $this->dumpDatabase($tmpSql);

            $files = $this->zip($dir . DIRECTORY_SEPARATOR . $name, $tmpSql);
            $backup->update(['filename' => $name, 'size' => filesize($dir . DIRECTORY_SEPARATOR . $name), 'files_count' => $files]);

            // Copy to Google Drive. A Drive failure keeps the local backup and is reported.
            $driveError = null;
            if (GoogleDrive::configured() && GoogleDrive::connected()) {
                try {
                    $drive = new GoogleDrive();
                    $backup->update(['drive_file_id' => $drive->upload($backup->localPath(), $name), 'drive_status' => 'uploaded']);
                    $drive->prune(config('backup.keep_days'));
                } catch (\Throwable $e) {
                    $driveError = $e->getMessage();
                    $backup->update(['drive_status' => 'failed']);
                }
            } else {
                $backup->update(['drive_status' => 'skipped']);
            }

            $this->pruneLocal();

            $backup->update([
                'status'      => $driveError ? 'failed' : 'success',
                'message'     => $driveError ? 'Saved on the server, but the Google Drive copy failed: ' . $driveError
                               : ($backup->drive_status === 'skipped' ? 'Saved on the server only — Google Drive is not connected.' : null),
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $backup->update(['status' => 'failed', 'message' => $e->getMessage(), 'finished_at' => now()]);
        } finally {
            if ($tmpSql && is_file($tmpSql)) @unlink($tmpSql);
        }

        ActivityLog::record(
            $backup->status === 'success' ? 'created' : 'failed', 'backup', $backup->id,
            $backup->status === 'success'
                ? "Backup {$backup->filename} made (" . $backup->sizeLabel() . ($backup->drive_status === 'uploaded' ? ', copied to Google Drive' : '') . ')'
                : 'Backup failed: ' . $backup->message,
        );

        if ($backup->status === 'failed') {
            $this->alert($backup);
        }
        return $backup;
    }

    /** mysqldump with the credentials in a temporary option file (never on the command line). */
    private function dumpDatabase(string $target): void
    {
        $db  = config('database.connections.' . config('database.default'));
        $cnf = tempnam(sys_get_temp_dir(), 'bss');
        file_put_contents($cnf, "[client]\nuser=\"{$db['username']}\"\npassword=\"" . addcslashes((string) $db['password'], '"\\') . "\"\nhost=\"{$db['host']}\"\nport=\"{$db['port']}\"\n");
        @chmod($cnf, 0600);

        $cmd = escapeshellarg(config('backup.mysqldump'))
            . ' --defaults-extra-file=' . escapeshellarg($cnf)
            . ' --single-transaction --quick --routines --triggers --no-tablespaces --default-character-set=utf8mb4'
            . ' --result-file=' . escapeshellarg($target)
            . ' ' . escapeshellarg($db['database']) . ' 2>&1';
        $out = [];
        exec($cmd, $out, $code);
        @unlink($cnf);

        if ($code !== 0 || !is_file($target) || filesize($target) < 100) {
            $why = trim(implode(' ', array_slice($out, 0, 3)));
            throw new RuntimeException('Database export failed: ' . ($why !== '' ? $why : 'unknown error'));
        }
    }

    /** One AES-256 ZIP: database.sql + uploads/. Returns how many upload files went in. */
    private function zip(string $target, string $sql): int
    {
        $zip = new ZipArchive();
        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the backup file.');
        }
        $zip->setPassword(config('backup.password'));
        $add = function (string $file, string $as) use ($zip) {
            $zip->addFile($file, $as);
            $zip->setEncryptionName($as, ZipArchive::EM_AES_256);
        };

        $add($sql, 'database.sql');
        $zip->addFromString('README.txt',
            "Barangay Smart System backup, " . now()->format('F d, Y g:i A') . "\r\n\r\n"
            . "database.sql  - the whole database. Restore: import it into an EMPTY MySQL database.\r\n"
            . "uploads/      - photos, signatures, logos and other uploads. Restore: copy into storage/app/public.\r\n");

        $count = 0;
        $root = storage_path('app/public');
        if (is_dir($root)) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if (!$f->isFile()) continue;
                $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));
                $add($f->getPathname(), 'uploads/' . $rel);
                $count++;
            }
        }
        if (!$zip->close()) {
            throw new RuntimeException('Could not finish the backup file.');
        }
        return $count;
    }

    /** Remove server copies older than keep_days. */
    private function pruneLocal(): void
    {
        $cutoff = now()->subDays(config('backup.keep_days'));
        Backup::whereNotNull('filename')->whereNull('deleted_local_at')->where('started_at', '<', $cutoff)->get()
            ->each(function (Backup $b) {
                if ($b->hasLocalFile()) @unlink($b->localPath());
                $b->update(['deleted_local_at' => now()]);
            });
    }

    /** Email every Super Admin. Mail problems never hide the failure itself (it is on the page). */
    private function alert(Backup $backup): void
    {
        $to = User::role('Super Admin')->where('is_active', 1)->pluck('email')->filter()->all();
        if (!$to) return;
        $brgy = \App\Models\Setting::instance()->barangay_name ?: 'Barangay Smart System';
        try {
            Mail::raw(
                "The automatic backup of {$brgy} did not complete on " . now()->format('M d, Y g:i A') . ".\n\n"
                . "Reason: {$backup->message}\n\n"
                . "Open Settings → Backups to see the details and run a backup again:\n" . url('settings/backups') . "\n",
                fn ($m) => $m->to($to)->subject("Backup failed — {$brgy}")
            );
        } catch (\Throwable $e) {
            $backup->update(['message' => $backup->message . ' (Alert email could not be sent: ' . $e->getMessage() . ')']);
        }
    }
}
