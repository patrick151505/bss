<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthCheckupMedicine extends Model
{
    protected $table = 'eb_health_checkup_medicines';

    protected $fillable = ['checkup_id', 'item_id', 'name', 'unit', 'quantity', 'instructions'];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
