<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryReleaseItem extends Model
{
    protected $table = 'eb_inventory_release_items';

    protected $fillable = ['release_id', 'item_id', 'quantity'];

    public function release()
    {
        return $this->belongsTo(InventoryRelease::class, 'release_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
