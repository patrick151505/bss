<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    protected $table = 'eb_inventory_categories';

    protected $fillable = ['name', 'slug', 'description', 'icon', 'color', 'is_active', 'is_medicine'];

    protected $casts = ['is_active' => 'boolean', 'is_medicine' => 'boolean'];

    public function items()
    {
        return $this->hasMany(InventoryItem::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Categories marked "Use for medicine" — their items can be given at a Health check-up. */
    public function scopeMedicine($query)
    {
        return $query->where('is_medicine', true);
    }
}
