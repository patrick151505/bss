<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'parent_id',
        'name',
        'section_label',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(BudgetLineItem::class, 'program_id');
    }

    /**
     * BBPF02 puts line items on the deepest node: top-level programs with
     * sub-programs are headings that only carry subtotals.
     */
    public function holdsLineItems(): bool
    {
        return $this->children()->doesntExist();
    }

    /** Top-level programs only, children eager-loaded in form order. */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')->orderBy('sort_order');
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
