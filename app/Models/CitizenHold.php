<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CitizenHold extends Model
{
    protected $table = 'eb_citizen_holds';

    protected $fillable = [
        'citizen_id', 'reason', 'blotter_id',
        'placed_by', 'placed_at',
        'lifted_by', 'lifted_at', 'lift_note',
    ];

    protected $casts = [
        'placed_at' => 'datetime',
        'lifted_at' => 'datetime',
    ];

    public function citizen()
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    /** The blotter case the hold was placed from, if any. */
    public function blotter()
    {
        return $this->belongsTo(Blotter::class, 'blotter_id');
    }

    public function placedBy()
    {
        return $this->belongsTo(User::class, 'placed_by');
    }

    public function liftedBy()
    {
        return $this->belongsTo(User::class, 'lifted_by');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('lifted_at');
    }

    public function isActive(): bool
    {
        return $this->lifted_at === null;
    }

    protected static function booted(): void
    {
        // Zone-limited accounts only see records of citizens in their zones.
        static::addGlobalScope(\App\Support\ZoneAccess::SCOPE, \App\Support\ZoneAccess::byCitizenColumn('citizen_id'));
    }
}
