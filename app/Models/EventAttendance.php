<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventAttendance extends Model
{
    protected $table = 'eb_event_attendance';
    public $timestamps = false;

    protected $fillable = [
        'eventId', 'citizenId', 'time_in', 'method',
    ];

    protected $casts = [
        'time_in'   => 'datetime',
        'eventId'   => 'integer',
        'citizenId' => 'integer',
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
