<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlotterParty extends Model
{
    protected $table = 'eb_blotter_parties';

    protected $fillable = [
        'blotter_id', 'role', 'citizen_id', 'name', 'address', 'contact', 'sort_order',
    ];

    public function blotter()
    {
        return $this->belongsTo(Blotter::class, 'blotter_id');
    }

    public function citizen()
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }
}
