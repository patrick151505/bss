<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $table = 'eb_inventory_items';

    protected $fillable = [
        'category_id', 'name', 'sku', 'image', 'unit', 'description',
        'stock', 'low_stock_threshold', 'requires_approval', 'is_rentable', 'is_schedulable',
        'schedule_color', 'schedule_buffer_days', 'is_active',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'is_rentable'       => 'boolean',
        'is_schedulable'    => 'boolean',
        'schedule_buffer_days' => 'integer',
        'is_active'         => 'boolean',
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

    /** Items lent out and expected back, rather than consumed. */
    public function scopeRentable(Builder $query): Builder
    {
        return $query->where('is_rentable', true);
    }

    /** Items reserved by date and time on the Schedule calendar (ambulance, hall…). */
    public function scopeSchedulable(Builder $query): Builder
    {
        return $query->where('is_schedulable', true);
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->low_stock_threshold;
    }
}
