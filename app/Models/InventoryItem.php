<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $table = 'eb_inventory_items';

    protected $fillable = [
        'category_id', 'name', 'sku', 'image', 'unit', 'description',
        'stock', 'low_stock_threshold', 'requires_approval', 'is_active',
    ];

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function stockIns()
    {
        return $this->hasMany(InventoryStockIn::class, 'item_id');
    }

    public function releaseItems()
    {
        return $this->hasMany(InventoryReleaseItem::class, 'item_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->low_stock_threshold;
    }
}
