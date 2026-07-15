<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Citizen extends Model
{
    protected $table = 'eb_citizen';

    public $timestamps = false;

    protected $fillable = [
        'qrcode',
        'familyId',
        'isHead',
        'relationId',
        'fname',
        'mname',
        'lname',
        'suffix',
        'bday',
        'bplace',
        'contact',
        'email',
        'gender',
        'civil_status',
        'is_soloparents',
        'address',
        'no',
        'street',
        'blk',
        'lot',
        'phase',
        'year_stay',
        'date_created',
        'date_approved',
        'approval_status',
        'date_last_updated',
        'user_id_approved',
        'is_active',
        'owner_status',
        'complete_address',
        'voters',
        'is_notify',
        'is_id_release',
        'pricinct_no',
        'note',
        'is_download',
        'is_verify',
        'ic_email',
        'ic_fullname',
        'ic_contact',
        'ic_address',
        'ic_relationship',
        'is_pwd',
        'citizenship',
        'occupation',
        'profile',
    ];

    protected $casts = [
        'bday'              => 'date',
        'year_stay'         => 'date',
        'date_created'      => 'datetime',
        'date_approved'     => 'datetime',
        'date_last_updated' => 'datetime',
        'gender'            => 'integer',
        'civil_status'      => 'integer',
        'address'           => 'integer',
        'approval_status'   => 'integer',
        'is_active'         => 'integer',
        'is_pwd'            => 'integer',
        'is_soloparents'    => 'integer',
        'voters'            => 'integer',
        'is_notify'         => 'integer',
        'is_id_release'     => 'integer',
        'is_verify'         => 'integer',
        'is_download'       => 'integer',
        'owner_status'      => 'integer',
        'blk'               => 'integer',
        'lot'               => 'integer',
        'phase'             => 'integer',
        'familyId'          => 'integer',
        'isHead'            => 'integer',
        'relationId'        => 'integer',
    ];

    // Relationships
    public function family()
    {
        return $this->belongsTo(Family::class, 'familyId');
    }

    public function relation()
    {
        return $this->belongsTo(Relation::class, 'relationId');
    }

    public function eventAttendances()
    {
        return $this->hasMany(EventAttendance::class, 'citizenId');
    }

    public function eventWins()
    {
        return $this->hasMany(EventWinner::class, 'citizenId');
    }

    public function inventoryReleases()
    {
        return $this->hasMany(InventoryRelease::class, 'citizen_id');
    }

    public function documentRequests()
    {
        return $this->hasMany(DocumentRequest::class, 'citizen_id');
    }

    public function getProfilePhotoAttribute(): string
    {
        if ($this->profile) {
            return asset(str_replace('public/', 'storage/', $this->profile));
        }
        return asset('images/default-avatar.png');
    }

    public function getIsHouseholdHeadAttribute(): bool
    {
        return $this->isHead === 2;
    }

    public function getIsFamilyHeadAttribute(): bool
    {
        return $this->isHead === 1;
    }

    public function citizenIds()
    {
        return $this->hasMany(CitizenId::class, 'citizen_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'eb_citizen_tag', 'citizen_id', 'tag_id');
    }

    public function blotterParties()
    {
        return $this->hasMany(BlotterParty::class, 'citizen_id');
    }

    public function blotters()
    {
        return $this->hasManyThrough(
            Blotter::class,
            BlotterParty::class,
            'citizen_id',
            'id',
            'id',
            'blotter_id'
        );
    }

    public function civilStatus()
    {
        return $this->belongsTo(CivilStatus::class, 'civil_status');
    }

    public function addressZone()
    {
        return $this->belongsTo(Address::class, 'address');
    }

    public function approvalStatus()
    {
        return $this->belongsTo(ApprovalStatus::class, 'approval_status');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'user_id_approved');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 1);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        $parts = [$this->fname, $this->mname, $this->lname];
        if ($this->suffix) {
            $parts[] = $this->suffix;
        }
        return implode(' ', array_filter($parts));
    }

    public function getAgeAttribute()
    {
        return $this->bday ? $this->bday->age : null;
    }
}
