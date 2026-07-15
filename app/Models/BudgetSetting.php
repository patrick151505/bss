<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetSetting extends Model
{
    protected $table = 'eb_budget_settings';

    // Valid base options for mandatory fund computation
    const BASES = [
        'nta'             => 'NTA (National Tax Allotment)',
        'general_fund'    => 'General Fund (total regular income)',
        'regular_sources' => 'Estimated Revenue from Regular Sources',
        'total_budget'    => 'Total Annual Budget',
    ];

    const MANDATORY_FUNDS = ['dev_fund', 'sk_fund', 'calamity', 'gad'];

    protected $fillable = [
        'barangay_name',
        'municipality',
        'province',
        'treasurer_name',
        'treasurer_position',
        'punong_barangay',
        'appropriation_chairman',
        'appropriation_chairman_position',
        'fund_account_no',
        'petty_cash_fund_limit',
        'ca_deadline_days_local',
        'ca_deadline_days_travel',
        // Voucher numbering
        'voucher_prefix_dv',
        'voucher_prefix_pcv',
        'voucher_prefix_payroll',
        'voucher_seq_padding',
        // Mandatory fund config
        'dev_fund_rate', 'dev_fund_base',
        'sk_fund_rate',  'sk_fund_base',
        'calamity_rate', 'calamity_base',
        'gad_rate',      'gad_base',
    ];

    protected $casts = [
        'petty_cash_fund_limit'   => 'decimal:2',
        'ca_deadline_days_local'  => 'integer',
        'ca_deadline_days_travel' => 'integer',
        'voucher_seq_padding'     => 'integer',
        'dev_fund_rate'           => 'decimal:2',
        'sk_fund_rate'            => 'decimal:2',
        'calamity_rate'           => 'decimal:2',
        'gad_rate'                => 'decimal:2',
    ];

    /** Resolve the prefix for a given voucher type key */
    public function voucherPrefix(string $type): string
    {
        return match($type) {
            'DV'      => $this->voucher_prefix_dv      ?: 'DV',
            'PCV'     => $this->voucher_prefix_pcv     ?: 'PCV',
            'Payroll' => $this->voucher_prefix_payroll ?: 'PAY',
            default   => $type,
        };
    }

    /** Zero-padding width for the sequence number */
    public function seqPadding(): int
    {
        return max(1, (int) ($this->voucher_seq_padding ?: 3));
    }

    public static function instance(): static
    {
        return static::firstOrCreate(['id' => 1]);
    }

    /**
     * Compute mandatory fund amounts from the given income pools.
     *
     * @param  array  $pools  ['nta'=>..., 'general_fund'=>..., 'regular_sources'=>..., 'total_budget'=>...]
     * @return array  ['dev_fund'=>..., 'sk_fund'=>..., 'calamity'=>..., 'gad'=>...]
     */
    public function computeMandatoryFunds(array $pools): array
    {
        $result = [];
        foreach (self::MANDATORY_FUNDS as $fund) {
            $base = $this->{"{$fund}_base"} ?? 'total_budget';
            $rate = (float) ($this->{"{$fund}_rate"} ?? 0);
            $pool = (float) ($pools[$base] ?? 0);
            $result[$fund] = round($pool * $rate / 100, 2);
        }
        return $result;
    }
}
