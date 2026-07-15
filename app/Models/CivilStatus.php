<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CivilStatus extends Model
{
    protected $table = 'eb_civil';

    public $timestamps = false;

    protected $fillable = ['description'];

    public function citizens()
    {
        return $this->hasMany(Citizen::class, 'civil_status');
    }
}
