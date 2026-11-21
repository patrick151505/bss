<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One medicine on a check-up's prescription (reseta) — bought outside, not from stock. */
class HealthPrescription extends Model
{
    protected $table = 'eb_health_prescriptions';

    protected $fillable = ['checkup_id', 'medicine', 'quantity', 'sig', 'sort_order'];

    public function checkup()
    {
        return $this->belongsTo(HealthCheckup::class, 'checkup_id');
    }
}
