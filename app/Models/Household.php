<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Household extends Model
{
    protected $table = 'eb_household';
    public $timestamps = false;

    protected $fillable = [
        'addressId', 'blk', 'lot', 'phaseStreet', 'no',
        'internal', 'completeAdress', 'citizenHeadId',
        'user_id', 'date_created', 'date_lastupdated',
    ];

    protected $casts = [
        'date_created'     => 'datetime',
        'date_lastupdated' => 'datetime',
        'blk'              => 'integer',
        'lot'              => 'integer',
        'addressId'        => 'integer',
        'citizenHeadId'    => 'integer',
        'user_id'          => 'integer',
    ];

    public function address()
    {
        return $this->belongsTo(Address::class, 'addressId');
    }

    public function householdHead()
    {
        return $this->belongsTo(Citizen::class, 'citizenHeadId');
    }

    public function families()
    {
        return $this->hasMany(Family::class, 'householdId');
    }

    public function getFamilyCountAttribute(): int
    {
        return $this->families()->count();
    }

    public function getMemberCountAttribute(): int
    {
        return Citizen::whereIn('familyId', $this->families()->pluck('id'))->count();
    }

    public function getFormattedIdAttribute(): string
    {
        return 'HH-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    public function getFullAddressAttribute(): string
    {
        $addr = $this->address?->description ?? '';

        if ($this->blk && $this->lot) {
            $parts = [];
            if ($this->blk) $parts[] = 'Blk ' . $this->blk;
            if ($this->lot) $parts[] = 'Lot ' . $this->lot;
            if ($this->phaseStreet) $parts[] = $this->phaseStreet;
            $parts[] = $addr;
            return implode(' ', $parts);
        }

        if ($this->no) {
            return trim($this->no . ' ' . $this->phaseStreet . ' ' . $addr);
        }

        return $this->completeAdress ?? $addr;
    }
}
