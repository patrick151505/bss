<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A kind of health check-up (General consultation, Prenatal, …). Picked or
 * typed on the check-up form, like document purposes.
 */
class HealthCheckupType extends Model
{
    protected $table = 'eb_health_checkup_types';

    protected $fillable = ['name', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Turn typed text into the saved type name: reuse an existing one when it
     * matches (any letter case), otherwise add it to the list for next time.
     */
    public static function resolve(?string $typed): ?string
    {
        $name = trim(preg_replace('/\s+/', ' ', (string) $typed));
        if ($name === '') {
            return null;
        }

        $existing = static::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            if (! $existing->is_active) {
                $existing->update(['is_active' => true]);
            }
            return $existing->name;
        }

        static::create([
            'name'       => mb_substr($name, 0, 100),
            'is_active'  => true,
            'sort_order' => (int) static::max('sort_order') + 1,
        ]);

        return mb_substr($name, 0, 100);
    }
}
