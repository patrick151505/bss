<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryRentalItem extends Model
{
    protected $table = 'eb_inventory_rental_items';

    const CONDITIONS_OUT = ['good' => 'Good', 'fair' => 'Fair', 'damaged' => 'Damaged'];
    const CONDITIONS_IN  = ['good' => 'Good', 'fair' => 'Fair', 'damaged' => 'Damaged', 'lost' => 'Lost'];

    protected $fillable = [
        'rental_id', 'item_id', 'quantity', 'quantity_returned',
        'condition_out', 'condition_in', 'condition_notes',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'quantity_returned' => 'integer',
    ];

    public function rental()
    {
        return $this->belongsTo(InventoryRental::class, 'rental_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    /** Units still with the borrower. */
    public function outstanding(): int
    {
        return max(0, $this->quantity - $this->quantity_returned);
    }

    public function isFullyReturned(): bool
    {
        return $this->outstanding() === 0;
    }

    /**
     * Lost units never come back, so they must not be added back to stock
     * when the line is settled.
     */
    public function isLost(): bool
    {
        return $this->condition_in === 'lost';
    }
}
