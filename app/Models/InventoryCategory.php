<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    protected $table = 'eb_inventory_categories';

    protected $fillable = ['name', 'slug', 'description', 'icon', 'color', 'is_active'];

    public function items()
    {
        return $this->hasMany(InventoryItem::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
