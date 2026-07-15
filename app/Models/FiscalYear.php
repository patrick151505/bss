<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalYear extends Model
{
    protected $table = 'eb_fiscal_years';

    protected $fillable = [
        'year', 'label', 'is_active', 'beginning_cash_balance', 'notes',
    ];

    protected $casts = [
        'is_active'              => 'boolean',
        'beginning_cash_balance' => 'decimal:2',
    ];

    public static function active(): ?self
    {
        return static::where('is_active', true)->latest('year')->first();
    }

    public function displayLabel(): string
    {
        return $this->label ?: "FY {$this->year}";
    }

    public function incomeEstimates()
    {
        return $this->hasMany(IncomeEstimate::class, 'fiscal_year_id')->orderBy('source_type');
    }

    public function allocations()
    {
        return $this->hasMany(BudgetAllocation::class, 'fiscal_year_id');
    }

    public function adjustments()
    {
        return $this->hasMany(BudgetAdjustment::class, 'fiscal_year_id');
    }

    public function transactions()
    {
        return $this->hasMany(BudgetTransaction::class, 'fiscal_year_id');
    }

    public function cashAdvances()
    {
        return $this->hasMany(CashAdvance::class, 'fiscal_year_id');
    }
}
