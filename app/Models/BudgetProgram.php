<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetProgram extends Model
{
    protected $table = 'eb_budget_programs';

    const SPECIAL_FUNDS = [
        'dev_fund' => 'Development Fund',
        'sk_fund'  => 'SK Fund',
        'calamity' => 'Calamity / LDRRM Fund',
        'gad'      => 'GAD Fund',
    ];

    protected $fillable = [
        'name',
        'special_fund',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function allocations(): HasMany
    {
        return $this->hasMany(BudgetAllocation::class, 'program_id');
    }

    public function isMandatory(): bool
    {
        return $this->special_fund !== null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
