<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetTemplateItem extends Model
{
    protected $table = 'eb_budget_template_items';

    protected $fillable = [
        'template_id',
        'program_id',
        'object_class',
        'name',
        'object_code',
        'aip_code',
        'expected_result',
        'performance_indicator',
        'group_label',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(BudgetTemplate::class, 'template_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(BudgetProgram::class, 'program_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CoaAccount::class, 'object_code', 'code');
    }
}
