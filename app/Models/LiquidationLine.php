<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiquidationLine extends Model
{
    protected $table = 'eb_liquidation_lines';

    protected $fillable = [
        'liquidation_report_id', 'or_no', 'receipt_date', 'particulars', 'amount',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'receipt_date' => 'date',
    ];

    public function report()
    {
        return $this->belongsTo(LiquidationReport::class, 'liquidation_report_id');
    }
}
