<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetSupplier extends Model
{
    protected $table = 'eb_budget_suppliers';

    const BUSINESS_TYPES = [
        'individual'  => 'Individual',
        'corporation' => 'Corporation',
        'na'          => 'N/A',
    ];

    const VAT_TYPES = [
        'vat'     => 'VAT-Registered',
        'non_vat' => 'Non-VAT',
        'exempt'  => 'VAT-Exempt',
    ];

    const PROVIDES = [
        'goods'    => 'Goods',
        'services' => 'Services',
        'both'     => 'Goods & Services',
    ];

    protected $fillable = [
        'name', 'tin', 'address', 'zip_code', 'line_of_business',
        'business_type', 'vat_type', 'provides',
        'contact_person', 'contact_no', 'email', 'is_active',
    ];

    public function tinRequired(): bool
    {
        return $this->vat_type === 'vat';
    }

    protected $casts = ['is_active' => 'boolean'];

    public function transactions(): HasMany
    {
        return $this->hasMany(BudgetTransaction::class, 'supplier_id');
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('is_active', true);
    }
}
