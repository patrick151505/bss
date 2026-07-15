<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table    = 'eb_activity_logs';
    public    $timestamps = false;

    protected $fillable = [
        'user_id', 'action', 'subject_type', 'subject_id',
        'description', 'properties', 'ip_address', 'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public static function record(
        string  $action,
        string  $subjectType,
        ?int    $subjectId   = null,
        string  $description = '',
        array   $properties  = []
    ): void {
        try {
            static::create([
                'user_id'      => auth()->id(),
                'action'       => $action,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId,
                'description'  => $description,
                'properties'   => $properties ?: null,
                'ip_address'   => request()->ip(),
                'created_at'   => now(),
            ]);
        } catch (\Throwable) {
            // Logging must never break the main request
        }
    }
}
