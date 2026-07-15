<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventWinner extends Model
{
    protected $table = 'eb_event_winner';
    public $timestamps = false;

    protected $fillable = [
        'eventId', 'citizenId', 'round', 'prize_label', 'drawn_at',
    ];

    protected $casts = [
        'drawn_at'  => 'datetime',
        'eventId'   => 'integer',
        'citizenId' => 'integer',
        'round'     => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'eventId');
    }

    public function citizen()
    {
        return $this->belongsTo(Citizen::class, 'citizenId');
    }
}
