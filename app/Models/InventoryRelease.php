<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryRelease extends Model
{
    protected $table = 'eb_inventory_releases';

    protected $fillable = [
        'citizen_id', 'released_by', 'status', 'purpose', 'remarks',
        'approved_by', 'approved_at', 'released_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function citizen()
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    public function releasedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'released_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(InventoryReleaseItem::class, 'release_id');
    }

    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isApproved(): bool  { return $this->status === 'approved'; }
    public function isReleased(): bool  { return $this->status === 'released'; }
    public function isRejected(): bool  { return $this->status === 'rejected'; }
}
