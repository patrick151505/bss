<?php

/*
|--------------------------------------------------------------------------
| Automatic backups (Settings → Backups)
|--------------------------------------------------------------------------
| Every Friday at 2:00 AM the database and the uploads folder are put into one
| password-protected ZIP (AES-256), kept on the server and copied to the
| barangay's Google Drive. Older than `keep_days` is deleted in both places.
*/

return [

    // Password for the backup ZIPs. Without it a backup cannot be opened —
    // keep a copy somewhere safe, outside the server.
    'password' => env('BACKUP_PASSWORD'),

    // When the automatic backup runs (Asia/Manila): weekly on this day, at this time.
    'day'  => env('BACKUP_DAY', 'friday'),
    'time' => env('BACKUP_TIME', '02:00'),

    // 28 days = the last 4 weekly backups (plus any made with "Back up now" in that time).
    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 28),

    // Where backups are kept on the server (never inside public/).
    'path' => storage_path('app/backups'),

    // mysqldump binary. On Linux servers it is normally on the PATH.
    'mysqldump' => env('BACKUP_MYSQLDUMP', 'mysqldump'),

    'google' => [
        'client_id'     => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        // Only files this app creates — it cannot see anything else in the Drive.
        'scope'         => 'https://www.googleapis.com/auth/drive.file',
        // Empty = "BSS Backups – <barangay name from Settings>", so each barangay's
        // folder is easy to tell apart even when several share one Google account.
        'folder_name'   => env('BACKUP_DRIVE_FOLDER'),
    ],
];
