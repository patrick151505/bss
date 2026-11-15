<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A date/time reservation of a schedulable inventory item (ambulance, hall,
 * vehicle…). Overlapping reservations of an item may never add up to more
 * than its stock.
 */
class InventorySchedule extends Model
{
    protected $table = 'eb_inventory_schedules';

    const STATUSES = [
        'reserved'  => 'Reserved',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'item_id', 'quantity', 'citizen_id', 'requester_name', 'contact',
        'purpose', 'location', 'notes', 'start_at', 'end_at', 'status',
        'cancel_reason', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
        'quantity' => 'integer',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function citizen()
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Who it's for: the citizen's name, or the typed name. */
    public function getForNameAttribute(): string
    {
        return $this->citizen?->full_name ?? ($this->requester_name ?: '—');
    }

    /**
     * Active (reserved) bookings of an item that clash with [$start, $end).
     * With a buffer of N days every booking also blocks the N days after it ends
     * (return, cleaning, checking) — the existing bookings' buffers and the new
     * booking's own buffer — so two bookings clash when
     *     new.start < old.end + N  and  old.start < new.end + N.
     */
    public function scopeOverlapping($query, int $itemId, $start, $end, ?int $exceptId = null, int $bufferDays = 0)
    {
        $start = \Carbon\Carbon::parse($start);
        $end   = \Carbon\Carbon::parse($end);
        return $query->where('item_id', $itemId)
            ->where('status', 'reserved')
            ->where('start_at', '<', $end->copy()->addDays($bufferDays))
            ->where('end_at', '>', $start->copy()->subDays($bufferDays))
            ->when($exceptId, fn($q) => $q->whereKeyNot($exceptId));
    }
}
