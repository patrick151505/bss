<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryStockIn extends Model
{
    protected $table = 'eb_inventory_stock_ins';

    protected $fillable = [
        'item_id', 'type', 'quantity', 'quantity_before', 'source', 'reference', 'notes', 'expiry_date', 'created_by',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
