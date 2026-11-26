<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal Google Drive client for backups (no extra packages).
 * OAuth "web application" flow with the drive.file scope: the app can only
 * see and manage the files it uploads itself.
 *
 * The connection (refresh token, account email, folder id) is stored
 * encrypted on eb_settings.backup_google.
 */
class GoogleDrive
{
    private const AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const API       = 'https://www.googleapis.com/drive/v3';
    private const UPLOAD    = 'https://www.googleapis.com/upload/drive/v3/files';

    private ?string $accessToken = null;

    public static function configured(): bool
    {
        return filled(config('backup.google.client_id')) && filled(config('backup.google.client_secret'));
    }

    /** Saved connection: ['refresh_token', 'email', 'folder_id', 'connected_at'] or null. */
    public static function connection(): ?array
    {
        $raw = Setting::instance()->backup_google;
        if (!$raw) return null;
        try {
            $data = json_decode(decrypt($raw), true);
        } catch (\Throwable) {
            return null;                       // APP_KEY changed — reconnect needed
        }
        return !empty($data['refresh_token']) ? $data : null;
    }

    public static function saveConnection(?array $data): void
    {
        $s = Setting::instance();
        $s->backup_google = $data ? encrypt(json_encode($data)) : null;
        $s->save();
    }

    /**
     * The address Google sends the user back to. Built from APP_URL (not from
     * whatever host the browser used), so it always matches the redirect URI
     * registered in Google Cloud exactly.
     */
    public static function callbackUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . '/settings/backups/google/callback';
    }

    public static function connected(): bool
    {
        return self::connection() !== null;
    }

    public static function authUrl(string $redirect, string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id'     => config('backup.google.client_id'),
            'redirect_uri'  => $redirect,
            'response_type' => 'code',
            'scope'         => config('backup.google.scope'),
            'access_type'   => 'offline',          // gives a refresh token for the nightly run
            'prompt'        => 'consent',          // always return a refresh token, even on reconnect
            'state'         => $state,
        ]);
    }

    /** Exchange the code from Google's callback and save the connection. Returns the account email. */
    public function connect(string $code, string $redirect): string
    {
        $res = Http::asForm()->timeout(30)->post(self::TOKEN_URL, [
            'code'          => $code,
            'client_id'     => config('backup.google.client_id'),
            'client_secret' => config('backup.google.client_secret'),
            'redirect_uri'  => $redirect,
            'grant_type'    => 'authorization_code',
        ]);
        if (!$res->ok() || !$res->json('refresh_token')) {
            throw new RuntimeException('Google did not allow the connection: ' . ($res->json('error_description') ?? $res->json('error') ?? $res->status()));
        }
        $this->accessToken = $res->json('access_token');
        $email = $this->api()->get(self::API . '/about', ['fields' => 'user(emailAddress)'])->json('user.emailAddress') ?? '';

        self::saveConnection([
            'refresh_token' => $res->json('refresh_token'),
            'email'         => $email,
            'folder_id'     => null,
            'connected_at'  => now()->toDateTimeString(),
        ]);
        return $email;
    }

    /** Revoke the token at Google and forget it here. */
    public static function disconnect(): void
    {
        if ($c = self::connection()) {
            try { Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/revoke', ['token' => $c['refresh_token']]); } catch (\Throwable) {}
        }
        self::saveConnection(null);
    }

    /** Upload a file into the backups folder. Returns the Drive file id. */
    public function upload(string $path, string $name): string
    {
        $folder = $this->folderId();
        $start = $this->api()->withHeaders([
            'X-Upload-Content-Type'   => 'application/zip',
            'X-Upload-Content-Length' => (string) filesize($path),
        ])->post(self::UPLOAD . '?uploadType=resumable&fields=id', ['name' => $name, 'parents' => [$folder]]);
        $session = $start->header('Location');
        if (!$start->ok() || !$session) {
            throw new RuntimeException('Could not start the Google Drive upload (' . $start->status() . ').');
        }

        $stream = fopen($path, 'rb');
        try {
            $put = Http::withToken($this->token())->timeout(1800)
                ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($stream), 'application/zip')
                ->put($session);
        } finally {
            if (is_resource($stream)) fclose($stream);
        }
        if (!$put->successful() || !$put->json('id')) {
            throw new RuntimeException('Google Drive upload failed (' . $put->status() . ').');
        }
        return $put->json('id');
    }

    /** Delete backups in the folder older than $days. Returns how many were removed. */
    public function prune(int $days): int
    {
        $folder = $this->folderId();
        $cutoff = now()->subDays($days)->toRfc3339String();
        $files = $this->api()->get(self::API . '/files', [
            'q'        => "'{$folder}' in parents and trashed = false and createdTime < '{$cutoff}'",
            'fields'   => 'files(id,name)',
            'pageSize' => 100,
        ])->json('files') ?? [];
        foreach ($files as $f) {
            $this->api()->delete(self::API . '/files/' . $f['id']);
        }
        return count($files);
    }

    /** "BSS Backups – Barangay San Jose" (or BACKUP_DRIVE_FOLDER when set). */
    public static function folderName(): string
    {
        if (filled(config('backup.google.folder_name'))) {
            return config('backup.google.folder_name');
        }
        $brgy = trim((string) Setting::instance()->barangay_name);
        if ($brgy === '') {
            return 'BSS Backups';
        }
        if (!preg_match('/^(barangay|brgy\.?)\s/i', $brgy)) {
            $brgy = 'Barangay ' . $brgy;
        }
        return 'BSS Backups – ' . $brgy;
    }

    // ── internals ──────────────────────────────────────────────────────

    private function token(): string
    {
        if ($this->accessToken) return $this->accessToken;
        $c = self::connection() ?? throw new RuntimeException('Google Drive is not connected.');
        $res = Http::asForm()->timeout(30)->post(self::TOKEN_URL, [
            'client_id'     => config('backup.google.client_id'),
            'client_secret' => config('backup.google.client_secret'),
            'refresh_token' => $c['refresh_token'],
            'grant_type'    => 'refresh_token',
        ]);
        if (!$res->ok()) {
            throw new RuntimeException($res->json('error') === 'invalid_grant'
                ? 'Google Drive access was removed or expired. Connect Google Drive again on Settings → Backups.'
                : 'Could not sign in to Google Drive (' . $res->status() . ').');
        }
        return $this->accessToken = $res->json('access_token');
    }

    private function api()
    {
        return Http::withToken($this->token())->acceptJson()->timeout(60);
    }

    /** The backups folder (created on first use, or again if someone deleted it). */
    private function folderId(): string
    {
        $c = self::connection();
        if (!empty($c['folder_id'])) {
            $f = $this->api()->get(self::API . '/files/' . $c['folder_id'], ['fields' => 'id,trashed']);
            if ($f->ok() && !$f->json('trashed')) {
                return $c['folder_id'];
            }
        }
        $res = $this->api()->post(self::API . '/files?fields=id', [
            'name'     => self::folderName(),
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);
        if (!$res->ok()) {
            throw new RuntimeException('Could not create the backups folder in Google Drive (' . $res->status() . ').');
        }
        $c['folder_id'] = $res->json('id');
        self::saveConnection($c);
        return $c['folder_id'];
    }
}
