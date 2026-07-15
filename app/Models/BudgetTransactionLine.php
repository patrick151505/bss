<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetTransactionLine extends Model
{
    protected $table = 'eb_budget_transaction_lines';

    protected $fillable = [
        'transaction_id',
        'line_item_id',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(BudgetTransaction::class, 'transaction_id');
    }

    public function lineItem(): BelongsTo
    {
        return $this->belongsTo(BudgetLineItem::class, 'line_item_id');
    }
}
