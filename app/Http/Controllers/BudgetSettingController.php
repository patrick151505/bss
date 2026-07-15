<?php

namespace App\Http\Controllers;

use App\Models\BudgetLog;
use App\Models\BudgetSetting;
use Illuminate\Http\Request;

class BudgetSettingController extends Controller
{
    public function index()
    {
        $settings = BudgetSetting::instance();
        return view('budget.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validBases = implode(',', array_keys(BudgetSetting::BASES));

        $data = $request->validate([
            'treasurer_name'                  => 'nullable|string|max:255',
            'treasurer_position'              => 'nullable|string|max:255',
            'appropriation_chairman'          => 'nullable|string|max:255',
            'appropriation_chairman_position' => 'nullable|string|max:255',
            'fund_account_no'         => 'nullable|string|max:100',
            'petty_cash_fund_limit'   => 'nullable|numeric|min:0',
            'ca_deadline_days_local'  => 'nullable|integer|min:1|max:365',
            'ca_deadline_days_travel' => 'nullable|integer|min:1|max:365',
            // Voucher numbering
            'voucher_prefix_dv'       => 'nullable|string|max:20',
            'voucher_prefix_pcv'      => 'nullable|string|max:20',
            'voucher_prefix_payroll'  => 'nullable|string|max:20',
            'voucher_seq_padding'     => 'nullable|integer|min:1|max:10',
            // Mandatory fund config
            'dev_fund_rate'  => 'nullable|numeric|min:0|max:100',
            'dev_fund_base'  => 'nullable|in:' . $validBases,
            'sk_fund_rate'   => 'nullable|numeric|min:0|max:100',
            'sk_fund_base'   => 'nullable|in:' . $validBases,
            'calamity_rate'  => 'nullable|numeric|min:0|max:100',
            'calamity_base'  => 'nullable|in:' . $validBases,
            'gad_rate'       => 'nullable|numeric|min:0|max:100',
            'gad_base'       => 'nullable|in:' . $validBases,
        ]);

        BudgetSetting::instance()->update($data);
        BudgetLog::record('updated', 'budget_settings', 1, 'Budget settings updated.');

        return back()->with('success', 'Settings saved.');
    }
}
