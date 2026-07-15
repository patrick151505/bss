<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiquidationReport extends Model
{
    protected $table = 'eb_liquidation_reports';

    const STATUSES = ['draft', 'closed'];

    protected $fillable = [
        'cash_advance_id', 'liquidation_date', 'total_expenses',
        'refund_amount', 'refund_or_no', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'total_expenses'   => 'decimal:2',
        'refund_amount'    => 'decimal:2',
        'liquidation_date' => 'date',
    ];

    public function cashAdvance()
    {
        return $this->belongsTo(CashAdvance::class, 'cash_advance_id');
    }

    public function lines()
    {
        return $this->hasMany(LiquidationLine::class, 'liquidation_report_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Compute the reconciliation balance.
     * Must equal zero for the report to close.
     * Balance = CA amount - total_expenses - refund_amount
     */
    public function reconciliationBalance(): float
    {
        $ca = (float) $this->cashAdvance->amount;
        return $ca - (float) $this->total_expenses - (float) $this->refund_amount;
    }

    public function canClose(): bool
    {
        return abs($this->reconciliationBalance()) < 0.01;
    }
}
