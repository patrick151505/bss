<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetAdjustment extends Model
{
    protected $table = 'eb_budget_adjustments';

    const TYPES = ['supplemental', 'realignment_in', 'realignment_out'];

    protected $fillable = [
        'fiscal_year_id', 'allocation_id', 'type', 'amount',
        'reference_no', 'effectivity_date', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'effectivity_date' => 'date',
    ];

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    public function allocation()
    {
        return $this->belongsTo(BudgetAllocation::class, 'allocation_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
