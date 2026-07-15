<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CitizenId extends Model
{
    protected $table = 'eb_citizen_ids';

    protected $fillable = ['citizen_id', 'generated_by', 'valid_until', 'sig_front'];

    protected $casts = ['valid_until' => 'date'];

    public function citizen()
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
