<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    protected $table = 'eb_family';
    public $timestamps = false;

    protected $fillable = [
        'householdId', 'citizenId', 'date_created', 'date_last_updated',
    ];

    protected $casts = [
        'date_created'      => 'datetime',
        'date_last_updated' => 'datetime',
        'householdId'       => 'integer',
        'citizenId'         => 'integer',
    ];

    public function household()
    {
        return $this->belongsTo(Household::class, 'householdId');
    }

    public function head()
    {
        return $this->belongsTo(Citizen::class, 'citizenId');
    }

    public function members()
    {
        return $this->hasMany(Citizen::class, 'familyId')->where('isHead', 0);
    }

    public function allMembers()
    {
        return $this->hasMany(Citizen::class, 'familyId');
    }

    public function getFormattedIdAttribute(): string
    {
        return 'FM-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }
}
