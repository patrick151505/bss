<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Backup;
use App\Support\BackupRunner;
use App\Support\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Settings → Backups (Super Admin only — a backup holds every resident's data). */
class BackupController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()?->hasRole('Super Admin'), 403, 'Only a Super Admin can manage backups.');
            return $next($request);
        });
    }

    public function index()
    {
        $heartbeat = storage_path('framework/scheduler-heartbeat');
        $last      = Backup::where('status', 'success')->latest('started_at')->first();

        return view('settings.backups', [
            'backups'       => Backup::with('triggeredBy:id,name')->latest('started_at')->limit(30)->get(),
            'lastSuccess'   => $last,
            // One backup a week: warn once the last good one is more than 8 days old.
            'overdue'       => !$last || $last->started_at->lt(now()->subDays(8)),
            'problems'      => BackupRunner::setupProblems(),
            'driveReady'    => GoogleDrive::configured(),
            'drive'         => GoogleDrive::connection(),
            'schedulerSeen' => is_file($heartbeat) ? \Carbon\Carbon::createFromTimestamp(filemtime($heartbeat)) : null,
            'time'          => \Carbon\Carbon::createFromFormat('H:i', config('backup.time'))->format('g:i A'),
            'day'           => ucfirst(strtolower(config('backup.day'))),
            'nextRun'       => now('Asia/Manila')->next(ucfirst(strtolower(config('backup.day'))))->setTimeFromTimeString(config('backup.time')),
            'keepDays'      => config('backup.keep_days'),
        ]);
    }

    public function run(Request $request, BackupRunner $runner)
    {
        $b = $runner->run($request->user()->id);

        return back()->with($b->status === 'success' ? 'success' : 'error',
            $b->status === 'success'
                ? "Backup made: {$b->filename} (" . $b->sizeLabel() . ').' . ($b->message ? ' ' . $b->message : '')
                : 'Backup failed: ' . $b->message);
    }

    public function download(Backup $backup)
    {
        abort_unless($backup->hasLocalFile(), 404, 'This backup is no longer on the server. Download it from Google Drive.');
        ActivityLog::record('downloaded', 'backup', $backup->id, "Backup {$backup->filename} downloaded");

        return response()->download($backup->localPath(), $backup->filename, ['Content-Type' => 'application/zip']);
    }

    public function connect(Request $request)
    {
        abort_unless(GoogleDrive::configured(), 422, 'Google Drive is not set up in the server .env yet.');

        // Opened under another address (127.0.0.1, no port…)? Switch to the APP_URL address
        // first, so Google's return trip lands on the same site and session.
        $appRoot = rtrim((string) config('app.url'), '/');
        if ($appRoot !== '' && rtrim($request->root(), '/') !== $appRoot) {
            return redirect()->away($appRoot . '/settings/backups/google/connect');
        }
        $state = Str::random(40);
        $request->session()->put('backup_google_state', $state);

        return redirect()->away(GoogleDrive::authUrl(GoogleDrive::callbackUrl(), $state));
    }

    public function callback(Request $request)
    {
        $expected = $request->session()->pull('backup_google_state');
        if (!$expected || !hash_equals($expected, (string) $request->query('state'))) {
            return redirect()->route('settings.backups')->with('error', 'The Google sign-in expired. Please click Connect Google Drive again.');
        }
        if ($request->filled('error')) {
            return redirect()->route('settings.backups')->with('error', 'Google Drive was not connected (' . $request->query('error') . ').');
        }

        try {
            $email = (new GoogleDrive())->connect((string) $request->query('code'), GoogleDrive::callbackUrl());
        } catch (\Throwable $e) {
            return redirect()->route('settings.backups')->with('error', $e->getMessage());
        }
        ActivityLog::record('updated', 'backup', null, "Google Drive connected for backups ({$email})");

        return redirect()->route('settings.backups')->with('success', "Google Drive connected ({$email}). Backups will be copied there every night.");
    }

    public function disconnect()
    {
        $email = GoogleDrive::connection()['email'] ?? '';
        GoogleDrive::disconnect();
        ActivityLog::record('updated', 'backup', null, "Google Drive disconnected from backups ({$email})");

        return back()->with('success', 'Google Drive disconnected. Backups are now kept on the server only.');
    }
}
