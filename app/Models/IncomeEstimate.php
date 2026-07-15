<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeEstimate extends Model
{
    protected $table = 'eb_income_estimates';

    const SOURCE_TYPES = [
        'nta'       => 'NTA (National Tax Allotment)',
        'rpt'       => 'Real Property Tax (RPT) Share',
        'clearance' => 'Barangay Clearance / Permits / Fees',
        'other'     => 'Other Regular Income',
    ];

    protected $fillable = [
        'fiscal_year_id',
        'source_type',
        'source_label',
        'estimated_amount',
        'two_years_ago_actual',
        'prior_year_actual',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'estimated_amount'    => 'decimal:2',
        'two_years_ago_actual'=> 'decimal:2',
        'prior_year_actual'   => 'decimal:2',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    /**
     * Compute income pools from a collection of estimates.
     * Returns the four pool totals needed for mandatory fund computation.
     */
    public static function computePools(\Illuminate\Support\Collection $estimates): array
    {
        $nta             = $estimates->where('source_type', 'nta')->sum('estimated_amount');
        $regularSources  = $estimates->sum('estimated_amount'); // all sources = regular sources
        $generalFund     = $regularSources;
        $totalBudget     = $regularSources;

        return [
            'nta'             => (float) $nta,
            'regular_sources' => (float) $regularSources,
            'general_fund'    => (float) $generalFund,
            'total_budget'    => (float) $totalBudget,
        ];
    }
}
