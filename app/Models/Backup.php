<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One backup run (Settings → Backups). */
class Backup extends Model
{
    protected $table = 'eb_backups';

    protected $fillable = [
        'filename', 'size', 'files_count', 'status', 'drive_status', 'drive_file_id',
        'message', 'triggered_by', 'started_at', 'finished_at', 'deleted_local_at',
    ];

    protected $casts = [
        'started_at'       => 'datetime',
        'finished_at'      => 'datetime',
        'deleted_local_at' => 'datetime',
    ];

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function localPath(): ?string
    {
        return $this->filename ? config('backup.path') . DIRECTORY_SEPARATOR . $this->filename : null;
    }

    public function hasLocalFile(): bool
    {
        return $this->localPath() && is_file($this->localPath());
    }

    public function driveUrl(): ?string
    {
        return $this->drive_file_id ? 'https://drive.google.com/file/d/' . $this->drive_file_id . '/view' : null;
    }

    public function sizeLabel(): string
    {
        $b = (int) $this->size;
        return $b >= 1048576 ? number_format($b / 1048576, 1) . ' MB' : number_format(max($b, 0) / 1024, 0) . ' KB';
    }
}
