<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * An item lent to a citizen and expected back — chairs, tents, sound
 * systems. Distinct from InventoryRelease, which hands over consumables
 * for good.
 */
class InventoryRental extends Model
{
    protected $table = 'eb_inventory_rentals';

    /** Lifecycle, in the order a rental moves through it. */
    const STATUSES = [
        'scheduled' => 'Scheduled',
        'out'       => 'Out',
        'returned'  => 'Returned',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'citizen_id', 'released_by', 'received_by', 'status',
        'borrowed_on', 'due_on', 'returned_on', 'released_at',
        'purpose', 'remarks',
        'rental_fee', 'deposit', 'fee_paid', 'deposit_refunded',
    ];

    protected $casts = [
        'borrowed_on'      => 'date',
        'due_on'           => 'date',
        'returned_on'      => 'date',
        'released_at'      => 'datetime',
        'rental_fee'       => 'decimal:2',
        'deposit'          => 'decimal:2',
        'fee_paid'         => 'boolean',
        'deposit_refunded' => 'boolean',
    ];

    public function citizen()
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    public function releasedBy()
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(InventoryRentalItem::class, 'rental_id');
    }

    public function isScheduled(): bool  { return $this->status === 'scheduled'; }
    public function isOut(): bool        { return $this->status === 'out'; }
    public function isReturned(): bool   { return $this->status === 'returned'; }
    public function isCancelled(): bool  { return $this->status === 'cancelled'; }

    /** Booked or already handed over — stock is reserved either way. */
    public function isActive(): bool
    {
        return $this->isScheduled() || $this->isOut();
    }

    /**
     * Only items actually with the borrower can be late. A booking whose
     * due date has passed was never picked up — that is a stale booking,
     * not an overdue rental.
     */
    public function isOverdue(): bool
    {
        return $this->isOut() && $this->due_on !== null && $this->due_on->isPast();
    }

    /** Booked, never collected, and the borrow date has already passed. */
    public function isMissedPickup(): bool
    {
        return $this->isScheduled() && $this->borrowed_on !== null && $this->borrowed_on->isPast();
    }

    /** Negative once overdue. */
    public function daysUntilDue(): ?int
    {
        return $this->due_on === null
            ? null
            : (int) now()->startOfDay()->diffInDays($this->due_on->startOfDay(), false);
    }

    public function daysOverdue(): int
    {
        return $this->isOverdue() ? abs($this->daysUntilDue()) : 0;
    }

    /** Total units still to come back across all lines. */
    public function outstandingQuantity(): int
    {
        return (int) $this->items->sum(fn ($i) => max(0, $i->quantity - $i->quantity_returned));
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeOut(Builder $query): Builder
    {
        return $query->where('status', 'out');
    }

    /** Booked or out — the rentals holding reserved stock. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['scheduled', 'out']);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'out')->whereDate('due_on', '<', now()->toDateString());
    }

    /** Out, not yet overdue, and due within the given window. */
    public function scopeDueSoon(Builder $query, int $days = 3): Builder
    {
        return $query->where('status', 'out')
            ->whereDate('due_on', '>=', now()->toDateString())
            ->whereDate('due_on', '<=', now()->addDays($days)->toDateString());
    }

    /** Bookings due for pickup within the given window. */
    public function scopePickupSoon(Builder $query, int $days = 3): Builder
    {
        return $query->where('status', 'scheduled')
            ->whereDate('borrowed_on', '<=', now()->addDays($days)->toDateString());
    }
}
