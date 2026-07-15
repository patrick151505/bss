<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetTransaction extends Model
{
    protected $table = 'eb_budget_transactions';

    protected $fillable = [
        'fiscal_year_id',
        'voucher_type',
        'voucher_no',
        'status',
        'fund_cluster',
        'amount',
        'tax_withheld',
        'tax_type',
        'transaction_date',
        'payee',
        'payee_tin',
        'payee_address',
        'payee_zip_code',
        'supplier_id',
        'mode_of_payment',
        'check_no',
        'check_date',
        'bank_name',
        'or_number',
        'or_date',
        'description',
        'recorded_by',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'tax_withheld'     => 'decimal:2',
        'transaction_date' => 'date',
        'check_date'       => 'date',
        'or_date'          => 'date',
    ];

    const VOUCHER_TYPES = [
        'DV'      => 'Disbursement Voucher',
        'PCV'     => 'Petty Cash Voucher',
        'Payroll' => 'Payroll',
    ];

    const PAYMENT_MODES = [
        'cash'          => 'Cash',
        'check'         => 'Check',
        'bank_transfer' => 'Bank Transfer',
    ];

    const STATUSES = [
        'draft'     => ['label' => 'Draft',     'color' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'],
        'approved'  => ['label' => 'Approved',  'color' => 'bg-primary/15 text-primary'],
        'paid'      => ['label' => 'Paid',       'color' => 'bg-success/15 text-success'],
        'cancelled' => ['label' => 'Cancelled',  'color' => 'bg-danger/15 text-danger'],
    ];

    /** Generate next voucher number using prefixes and padding from BudgetSetting */
    public static function generateVoucherNo(string $type, int $fiscalYearId): string
    {
        $setting = BudgetSetting::instance();
        $prefix  = $setting->voucherPrefix($type);
        $padding = $setting->seqPadding();
        $year    = FiscalYear::find($fiscalYearId)?->year ?? date('Y');

        $last = static::where('fiscal_year_id', $fiscalYearId)
            ->where('voucher_type', $type)
            ->whereNotNull('voucher_no')
            ->orderByDesc('id')
            ->value('voucher_no');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return "{$prefix}-{$year}-" . str_pad($seq, $padding, '0', STR_PAD_LEFT);
    }

    public function netAmount(): float
    {
        return (float) $this->amount - (float) $this->tax_withheld;
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetTransactionLine::class, 'transaction_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(BudgetTransactionAttachment::class, 'transaction_id')->orderBy('created_at');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(BudgetSupplier::class, 'supplier_id');
    }
}
