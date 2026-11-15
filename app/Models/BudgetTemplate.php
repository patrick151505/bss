<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The BBPF02 worksheet as editable data: the standard set of accounts a
 * barangay lists under each program. Opening a fiscal year copies the
 * template into line items with blank amounts.
 */
class BudgetTemplate extends Model
{
    protected $table = 'eb_budget_templates';

    protected $fillable = [
        'name',
        'description',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(BudgetTemplateItem::class, 'template_id');
    }

    public static function default(): ?self
    {
        return static::where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }
}
