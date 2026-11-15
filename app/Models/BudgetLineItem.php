<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetLineItem extends Model
{
    protected $table = 'eb_budget_line_items';

    /** Expense classes on BBPF02, in the order the form prints them. */
    const OBJECT_CLASSES = [
        'PS'   => 'Personal Services (PS)',
        'MOOE' => 'Maintenance and Other Operating Expenses (MOOE)',
        'FE'   => 'Financial Expenses',
        'CO'   => 'Capital Outlay (CO)',
    ];

    /** AIP reference-code series per expense class, e.g. MOOE -> "3000-07". */
    const AIP_SERIES = [
        'PS'   => '1000',
        'MOOE' => '3000',
        'FE'   => '9000',
        'CO'   => '8000',
    ];

    protected $fillable = [
        'fiscal_year_id',
        'program_id',
        'object_class',
        'object_code',
        'aip_code',
        'name',
        'appropriation',
        'expected_result',
        'performance_indicator',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'appropriation' => 'decimal:2',
        'sort_order'    => 'integer',
    ];

    /**
     * A null appropriation means the account is listed on the form but not
     * funded this year — BBPF02 keeps such rows and prints them blank. It is
     * deliberately distinct from a stored 0.00.
     */
    public function isFunded(): bool
    {
        return $this->appropriation !== null && (float) $this->appropriation != 0.0;
    }

    /** Accounts carrying a real appropriation — the budget proper. */
    public function scopeFunded($query)
    {
        return $query->whereNotNull('appropriation')->where('appropriation', '!=', 0);
    }

    /** Accounts listed on the form but not funded this year. */
    public function scopeUnfunded($query)
    {
        return $query->where(fn ($q) => $q->whereNull('appropriation')->orWhere('appropriation', 0));
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CoaAccount::class, 'object_code', 'code');
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(BudgetProgram::class, 'program_id');
    }

    public function transactionLines(): HasMany
    {
        // BudgetTransactionLine is created when vouchers module is built
        return $this->hasMany('App\Models\BudgetTransactionLine', 'line_item_id');
    }

    public function cashAdvances(): HasMany
    {
        return $this->hasMany(CashAdvance::class, 'line_item_id');
    }

    /** Total approved + paid voucher lines against this line item */
    public function disbursed(): float
    {
        return (float) $this->transactionLines()
            ->whereHas('transaction', fn($q) => $q->whereIn('status', ['approved', 'paid']))
            ->sum('amount');
    }

    /**
     * Total un-liquidated (open) cash advances charged to this line item.
     * Cash advances are currently linked to allocations, not line items, so this
     * returns 0 unless the eb_cash_advances table gains a line_item_id column.
     */
    public function cashAdvanceTotal(): float
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('eb_cash_advances', 'line_item_id')) {
            return 0.0;
        }

        return (float) $this->cashAdvances()->where('status', 'open')->sum('amount');
    }

    /** Remaining balance = appropriation − disbursed − open cash advances */
    public function balance(): float
    {
        return (float) $this->appropriation - $this->disbursed() - $this->cashAdvanceTotal();
    }
}
