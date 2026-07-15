<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetLineItem extends Model
{
    protected $table = 'eb_budget_line_items';

    // Default line items with COA object codes — [name, object_code]
    const DEFAULTS = [
        'PS'   => [
            ['Salaries and Wages',                                '50101010'],
            ['Honoraria',                                         '50102010'],
            ['Personnel Economic Relief Allowance (PERA)',        '50102020'],
            ['Year-End Bonus',                                    '50102030'],
            ['Cash Gift',                                         '50102040'],
            ['Life and Retirement Insurance Contributions (GSIS)','50103010'],
            ['PhilHealth Contributions',                          '50103020'],
            ['Pag-IBIG Contributions',                            '50103030'],
        ],
        'MOOE' => [
            ['Office Supplies Expense',      '50203010'],
            ['Electricity Expense',          '50204010'],
            ['Water Expense',                '50204020'],
            ['Communication Expense',        '50205010'],
            ['Traveling Expense (Local)',     '50201010'],
            ['Training Expense',             '50202010'],
            ['Fuel, Oil and Lubricants',     '50203090'],
            ['Repairs and Maintenance',      '50213060'],
            ['Printing and Publication',     '50299010'],
            ['Other MOOE',                   '50299990'],
        ],
        'CO'   => [
            ['Office Equipment',             '10604010'],
            ['Furniture and Fixtures',       '10604020'],
            ['IT Equipment and Software',    '10605020'],
            ['Infrastructure Projects',      '10607010'],
            ['Other Capital Outlay',         '10699990'],
        ],
    ];

    protected $fillable = [
        'fiscal_year_id',
        'program_id',
        'object_class',
        'object_code',
        'name',
        'appropriation',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'appropriation' => 'decimal:2',
        'sort_order'    => 'integer',
    ];

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

    /** Remaining balance (cash advance deduction added when CA module is built) */
    public function balance(): float
    {
        return (float) $this->appropriation - $this->disbursed();
    }
}
