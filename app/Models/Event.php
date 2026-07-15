<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Event extends Model
{
    protected $table = 'eb_event';
    public $timestamps = false;

    protected $fillable = [
        'title', 'description', 'category', 'venue',
        'event_date', 'event_start', 'event_end',
        'cover_photo', 'raffle_enabled', 'allow_winner_repeat',
        'raffle_pin', 'raffle_pin_expires_at',
        'status', 'user_id', 'date_created', 'date_updated',
    ];

    protected $casts = [
        'event_date'            => 'date',
        'date_created'          => 'datetime',
        'date_updated'          => 'datetime',
        'raffle_enabled'        => 'integer',
        'allow_winner_repeat'   => 'integer',
        'raffle_pin_expires_at' => 'datetime',
        'user_id'               => 'integer',
    ];

    public function hasValidPin(): bool
    {
        return $this->raffle_pin &&
               $this->raffle_pin_expires_at &&
               now()->lt($this->raffle_pin_expires_at);
    }

    public function attendance()
    {
        return $this->hasMany(EventAttendance::class, 'eventId');
    }

    public function winners()
    {
        return $this->hasMany(EventWinner::class, 'eventId')->orderBy('round');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getAttendanceCountAttribute(): int
    {
        return $this->attendance()->count();
    }

    public function getIsOpenAttribute(): bool
    {
        if ($this->status === 'closed') return false;

        // Auto-close check: if event_date + event_end has passed
        if ($this->event_date && $this->event_end) {
            $end = Carbon::parse($this->event_date->format('Y-m-d') . ' ' . $this->event_end);
            return now()->lt($end);
        }

        // If only date set, close after end of that day
        if ($this->event_date) {
            return now()->lt($this->event_date->endOfDay());
        }

        return true;
    }

    public function getEffectiveStatusAttribute(): string
    {
        return $this->is_open ? 'open' : 'closed';
    }

    public function getCoverPhotoUrlAttribute(): ?string
    {
        return $this->cover_photo
            ? asset(str_replace('public/', 'storage/', $this->cover_photo))
            : null;
    }
}
