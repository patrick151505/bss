<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetLog extends Model
{
    protected $table    = 'eb_budget_logs';
    public    $timestamps = false;

    protected $fillable = [
        'user_id', 'action', 'module', 'subject_id',
        'description', 'properties', 'ip_address', 'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    const ACTION_COLORS = [
        'created'  => 'bg-success/15 text-success',
        'updated'  => 'bg-primary/15 text-primary',
        'deleted'  => 'bg-danger/15 text-danger',
        'uploaded' => 'bg-warning/15 text-warning',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Record an audit entry.
     *
     * New-style:  BudgetLog::record('allocation.saved', $model, ['key' => 'val'])
     * Old-style:  BudgetLog::record('created', 'allocation', 42, 'Description', [...])
     */
    public static function record(
        string $action,
        mixed  $subjectOrModule = null,
        mixed  $propertiesOrId  = null,
        string $description     = '',
        array  $properties      = []
    ): void {
        try {
            // New-style: second arg is an Eloquent model
            if ($subjectOrModule instanceof Model) {
                $model      = $subjectOrModule;
                $parts      = explode('.', $action, 2);
                $module     = $parts[0];
                $action     = $parts[1] ?? $parts[0];
                $subjectId  = $model->getKey();
                $properties = is_array($propertiesOrId) ? $propertiesOrId : [];
                $description= '';
            } else {
                // Old-style: string module
                $module     = $subjectOrModule ?? '';
                $subjectId  = is_int($propertiesOrId) ? $propertiesOrId : null;
                // $description and $properties passed as 4th/5th args
            }

            static::create([
                'user_id'     => auth()->id(),
                'action'      => $action,
                'module'      => $module,
                'subject_id'  => $subjectId ?? null,
                'description' => $description,
                'properties'  => $properties ?: null,
                'ip_address'  => request()->ip(),
                'created_at'  => now(),
            ]);
        } catch (\Throwable) {
            // Logging must never break the main request
        }
    }
}
