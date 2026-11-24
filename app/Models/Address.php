<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'eb_address';

    public $timestamps = false;

    protected $fillable = [
        'description',
        'is_subd',
        'rules_required',
        'is_active',
    ];

    protected $casts = [
        'is_subd'   => 'integer',
        'is_active' => 'boolean',
    ];

    public function citizens()
    {
        return $this->hasMany(Citizen::class, 'address');
    }

    protected static function booted(): void
    {
        // Zone-limited accounts only see their zones (Settings → Users).
        static::addGlobalScope(\App\Support\ZoneAccess::SCOPE, \App\Support\ZoneAccess::byZoneColumn('id'));
    }
}
