<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An account from the COA Revised Chart of Accounts for LGUs
 * (COA Circular 2015-009). Read-mostly reference data — the source of truth
 * for account codes and titles printed on BBPF02 and on disbursement
 * vouchers.
 */
class CoaAccount extends Model
{
    protected $table      = 'eb_coa_accounts';
    protected $primaryKey = 'code';
    protected $keyType    = 'string';
    public $incrementing  = false;

    protected $fillable = [
        'code',
        'title',
        'account_type',
        'account_group',
        'object_class',
        'is_budgetable',
        'is_active',
    ];

    protected $casts = [
        'is_budgetable' => 'boolean',
        'is_active'     => 'boolean',
    ];

    /** Accounts that may be budgeted against on BBPF02. */
    public function scopeBudgetable($query)
    {
        return $query->where('is_active', true)
            ->where('is_budgetable', true)
            ->whereNotNull('object_class');
    }

    public function scopeOfClass($query, string $objectClass)
    {
        return $query->where('object_class', $objectClass);
    }

    /** Revenue accounts, for the BBPF01 income side. */
    public function scopeIncome($query)
    {
        return $query->where('is_active', true)->where('account_type', 'Income');
    }

    public function label(): string
    {
        return "{$this->code} — {$this->title}";
    }
}
