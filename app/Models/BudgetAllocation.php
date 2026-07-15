<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetAllocation extends Model
{
    protected $table = 'eb_budget_allocations';

    const OBJECT_CLASSES = ['PS', 'MOOE', 'CO'];

    protected $fillable = [
        'fiscal_year_id',
        'program_id',
        'object_class',
        'appropriation',
        'computed_amount',
        'notes',
    ];

    protected $casts = [
        'appropriation'   => 'decimal:2',
        'computed_amount' => 'decimal:2',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(BudgetProgram::class, 'program_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BudgetTransaction::class, 'allocation_id');
    }

    public function disbursed(): float
    {
        return (float) $this->transactions()
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');
    }

    public function balance(): float
    {
        return (float) $this->appropriation - $this->disbursed();
    }
}
