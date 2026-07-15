<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CashAdvance extends Model
{
    protected $table = 'eb_cash_advances';

    const STATUSES = ['open', 'liquidated', 'cancelled'];

    protected $fillable = [
        'ca_no', 'fiscal_year_id', 'officer_id', 'allocation_id',
        'purpose', 'amount', 'date_granted', 'deadline_date',
        'status', 'approved_by', 'reference_no', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'date_granted'  => 'date',
        'deadline_date' => 'date',
    ];

    public static function generateCaNo(int $year): string
    {
        $last = static::where('ca_no', 'like', "CA-{$year}-%")->count();
        return sprintf('CA-%d-%03d', $year, $last + 1);
    }

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    public function officer()
    {
        return $this->belongsTo(AccountableOfficer::class, 'officer_id');
    }

    public function allocation()
    {
        return $this->belongsTo(BudgetAllocation::class, 'allocation_id');
    }

    public function liquidationReport()
    {
        return $this->hasOne(LiquidationReport::class, 'cash_advance_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->deadline_date->isPast();
    }

    public function daysOverdue(): int
    {
        return $this->isOverdue() ? (int) $this->deadline_date->diffInDays(now()) : 0;
    }
}
