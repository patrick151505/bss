@extends('layouts.vertical', [
    'title'         => 'Budget Settings',
    'sub_title'     => 'Budget',
    'sub_title_url' => route('budget.index'),
    'tagline'       => 'Configure fund rates, officials, and voucher settings',
])

@section('content')

@php
    $bases = \App\Models\BudgetSetting::BASES;
    $funds = [
        'dev_fund' => ['label' => 'Development Fund',  'basis_note' => 'RA 7160 §287 — 20% of NTA'],
        'sk_fund'  => ['label' => 'SK Fund',           'basis_note' => 'RA 7160 §328 — 10% of General Fund'],
        'calamity' => ['label' => 'Calamity / LDRRM', 'basis_note' => 'RA 10121 — 5% of regular sources'],
        'gad'      => ['label' => 'GAD Fund',          'basis_note' => 'PCW-NAPC-DBM §2014-01 — 5% of total budget'],
    ];
@endphp

@if(session('success'))
<div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('budget.settings.update') }}">
@csrf

<div class="space-y-4">

    {{-- ── 1. Mandatory Fund Configuration ── --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <button type="button" onclick="toggleSection('sec-funds', this)"
            class="w-full flex items-center justify-between px-5 py-3.5 border-b dark:border-gray-700 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
            <div class="flex items-center gap-3">
                <i class="mgc_pie_chart_line text-primary text-lg"></i>
                <div>
                    <div class="font-semibold text-sm text-gray-800 dark:text-gray-100">Mandatory Fund Configuration</div>
                    <div class="text-[10px] text-gray-400 mt-0.5">Development Fund, SK, Calamity, GAD — rates and income bases</div>
                </div>
            </div>
            <i class="mgc_up_line text-gray-400 transition-transform duration-200" id="icon-sec-funds"></i>
        </button>
        <div id="sec-funds" class="p-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b dark:border-gray-700 text-[10px] uppercase tracking-wide text-gray-400">
                        <th class="pb-2 text-left font-medium w-40">Fund</th>
                        <th class="pb-2 text-left font-medium w-28">Rate (%)</th>
                        <th class="pb-2 text-left font-medium">Base (Income Pool)</th>
                        <th class="pb-2 text-left font-medium text-gray-300 dark:text-gray-600">Statutory Basis</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @foreach($funds as $key => $cfg)
                    <tr>
                        <td class="py-2.5 font-medium text-gray-700 dark:text-gray-200">{{ $cfg['label'] }}</td>
                        <td class="py-2.5 pr-4">
                            <input type="number" name="{{ $key }}_rate"
                                class="form-input py-1.5 text-sm w-full text-right tabular-nums"
                                step="0.01" min="0" max="100"
                                value="{{ old("{$key}_rate", $settings->{"{$key}_rate"}) }}"
                                placeholder="0.00">
                        </td>
                        <td class="py-2.5 pr-4">
                            <select name="{{ $key }}_base" class="form-select py-1.5 text-sm w-full">
                                @foreach($bases as $bk => $blabel)
                                <option value="{{ $bk }}" {{ old("{$key}_base", $settings->{"{$key}_base"}) == $bk ? 'selected' : '' }}>
                                    {{ $blabel }}
                                </option>
                                @endforeach
                            </select>
                        </td>
                        <td class="py-2.5 text-xs text-gray-400">{{ $cfg['basis_note'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── 2. Officials (DV Signatories) ── --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <button type="button" onclick="toggleSection('sec-officials', this)"
            class="w-full flex items-center justify-between px-5 py-3.5 border-b dark:border-gray-700 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
            <div class="flex items-center gap-3">
                <i class="mgc_user_3_line text-primary text-lg"></i>
                <div>
                    <div class="font-semibold text-sm text-gray-800 dark:text-gray-100">Officials / DV Signatories</div>
                    <div class="text-[10px] text-gray-400 mt-0.5">Names and positions printed on Disbursement Vouchers</div>
                </div>
            </div>
            <i class="mgc_up_line text-gray-400 transition-transform duration-200" id="icon-sec-officials"></i>
        </button>
        <div id="sec-officials" class="divide-y divide-gray-100 dark:divide-gray-700/50">

            {{-- Box A --}}
            <div class="px-5 py-4">
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded text-[10px] font-bold bg-primary/10 text-primary">A</span>
                    <div>
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">Committee on Appropriations Chairman</span>
                        <span class="text-[10px] text-gray-400 ml-1">— Certified: availability of appropriation</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-400 mb-1 block">Name</label>
                        <input type="text" name="appropriation_chairman" class="form-input w-full text-sm"
                            value="{{ old('appropriation_chairman', $settings->appropriation_chairman) }}"
                            placeholder="Full name">
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 mb-1 block">Position / Title</label>
                        <input type="text" name="appropriation_chairman_position" class="form-input w-full text-sm"
                            value="{{ old('appropriation_chairman_position', $settings->appropriation_chairman_position ?? 'Chairman, Committee on Appropriations') }}"
                            placeholder="Chairman, Committee on Appropriations">
                    </div>
                </div>
            </div>

            {{-- Box B --}}
            <div class="px-5 py-4">
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded text-[10px] font-bold bg-success/10 text-success">B</span>
                    <div>
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">Barangay Treasurer</span>
                        <span class="text-[10px] text-gray-400 ml-1">— Certified: availability of funds</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-400 mb-1 block">Name</label>
                        <input type="text" name="treasurer_name" class="form-input w-full text-sm"
                            value="{{ old('treasurer_name', $settings->treasurer_name) }}"
                            placeholder="Full name">
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 mb-1 block">Position / Title</label>
                        <input type="text" name="treasurer_position" class="form-input w-full text-sm"
                            value="{{ old('treasurer_position', $settings->treasurer_position ?? 'Barangay Treasurer') }}"
                            placeholder="Barangay Treasurer">
                    </div>
                </div>
            </div>

            {{-- Box C — read-only note --}}
            <div class="px-5 py-4 bg-gray-50/50 dark:bg-gray-700/20">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded text-[10px] font-bold bg-warning/10 text-warning">C</span>
                    <div>
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">Punong Barangay</span>
                        <span class="text-[10px] text-gray-400 ml-1">— Approved for Payment</span>
                    </div>
                    <span class="ml-auto text-[10px] text-gray-400 flex items-center gap-1">
                        <i class="mgc_link_line"></i>
                        Pulled from <a href="{{ route('settings.index') }}" class="text-primary hover:underline">Global Settings → Captain Name</a>
                    </span>
                </div>
            </div>

        </div>
    </div>

    {{-- ── 3. Voucher Numbering ── --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <button type="button" onclick="toggleSection('sec-voucher', this)"
            class="w-full flex items-center justify-between px-5 py-3.5 border-b dark:border-gray-700 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
            <div class="flex items-center gap-3">
                <i class="mgc_hashtag_line text-primary text-lg"></i>
                <div>
                    <div class="font-semibold text-sm text-gray-800 dark:text-gray-100">Voucher Numbering</div>
                    <div class="text-[10px] text-gray-400 mt-0.5">Prefix and sequence padding for DV, PCV, and Payroll</div>
                </div>
            </div>
            <i class="mgc_up_line text-gray-400 transition-transform duration-200" id="icon-sec-voucher"></i>
        </button>
        <div id="sec-voucher" class="p-5">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">DV Prefix</label>
                    <input type="text" name="voucher_prefix_dv" class="form-input w-full text-sm font-mono"
                        value="{{ old('voucher_prefix_dv', $settings->voucher_prefix_dv ?? 'DV') }}"
                        placeholder="DV" maxlength="20">
                    <p class="text-[10px] text-gray-400 mt-1">Disbursement Voucher</p>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">PCV Prefix</label>
                    <input type="text" name="voucher_prefix_pcv" class="form-input w-full text-sm font-mono"
                        value="{{ old('voucher_prefix_pcv', $settings->voucher_prefix_pcv ?? 'PCV') }}"
                        placeholder="PCV" maxlength="20">
                    <p class="text-[10px] text-gray-400 mt-1">Petty Cash Voucher</p>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Payroll Prefix</label>
                    <input type="text" name="voucher_prefix_payroll" class="form-input w-full text-sm font-mono"
                        value="{{ old('voucher_prefix_payroll', $settings->voucher_prefix_payroll ?? 'PAY') }}"
                        placeholder="PAY" maxlength="20">
                    <p class="text-[10px] text-gray-400 mt-1">Payroll Voucher</p>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Sequence Zeros</label>
                    <input type="number" name="voucher_seq_padding" class="form-input w-full text-sm"
                        min="1" max="10"
                        value="{{ old('voucher_seq_padding', $settings->voucher_seq_padding ?? 3) }}">
                    <p class="text-[10px] text-gray-400 mt-1">3 → <span class="font-mono">001</span> &nbsp; 4 → <span class="font-mono">0001</span></p>
                </div>
            </div>
            <div class="mt-4 px-4 py-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/40 text-xs text-gray-500 dark:text-gray-400 flex flex-wrap gap-4">
                <span>Preview:</span>
                <span class="font-mono font-semibold text-primary" id="prefix-preview">
                    {{ ($settings->voucher_prefix_dv ?? 'DV') }}-{{ date('Y') }}-{{ str_pad(1, $settings->voucher_seq_padding ?? 3, '0', STR_PAD_LEFT) }}
                </span>
                <span class="font-mono font-semibold text-green-600 dark:text-green-400" id="prefix-preview-pcv">
                    {{ ($settings->voucher_prefix_pcv ?? 'PCV') }}-{{ date('Y') }}-{{ str_pad(1, $settings->voucher_seq_padding ?? 3, '0', STR_PAD_LEFT) }}
                </span>
                <span class="font-mono font-semibold text-purple-600 dark:text-purple-400" id="prefix-preview-pay">
                    {{ ($settings->voucher_prefix_payroll ?? 'PAY') }}-{{ date('Y') }}-{{ str_pad(1, $settings->voucher_seq_padding ?? 3, '0', STR_PAD_LEFT) }}
                </span>
            </div>
        </div>
    </div>

    {{-- ── 4. Bank / Fund Account ── --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <button type="button" onclick="toggleSection('sec-bank', this)"
            class="w-full flex items-center justify-between px-5 py-3.5 border-b dark:border-gray-700 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
            <div class="flex items-center gap-3">
                <i class="mgc_bank_line text-primary text-lg"></i>
                <div>
                    <div class="font-semibold text-sm text-gray-800 dark:text-gray-100">Bank / Fund Account</div>
                    <div class="text-[10px] text-gray-400 mt-0.5">Account number used on vouchers and reports</div>
                </div>
            </div>
            <i class="mgc_up_line text-gray-400 transition-transform duration-200" id="icon-sec-bank"></i>
        </button>
        <div id="sec-bank" class="p-5">
            <div class="max-w-sm">
                <label class="text-xs text-gray-400 mb-1 block">Fund Account No.</label>
                <input type="text" name="fund_account_no" class="form-input w-full text-sm"
                    value="{{ old('fund_account_no', $settings->fund_account_no) }}"
                    placeholder="e.g. Land Bank account number">
            </div>
        </div>
    </div>

    {{-- ── 5. Cash Advance Controls ── --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <button type="button" onclick="toggleSection('sec-ca', this)"
            class="w-full flex items-center justify-between px-5 py-3.5 border-b dark:border-gray-700 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
            <div class="flex items-center gap-3">
                <i class="mgc_wallet_line text-primary text-lg"></i>
                <div>
                    <div class="font-semibold text-sm text-gray-800 dark:text-gray-100">Cash Advance Controls</div>
                    <div class="text-[10px] text-gray-400 mt-0.5">COA Circular 97-002 — limits and liquidation deadlines</div>
                </div>
            </div>
            <i class="mgc_up_line text-gray-400 transition-transform duration-200" id="icon-sec-ca"></i>
        </button>
        <div id="sec-ca" class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Petty Cash Fund Limit (₱)</label>
                    <input type="number" name="petty_cash_fund_limit" class="form-input w-full text-sm text-right tabular-nums"
                        step="0.01" min="0"
                        value="{{ old('petty_cash_fund_limit', $settings->petty_cash_fund_limit ?? 5000) }}">
                    <p class="text-[10px] text-gray-400 mt-1">Maximum PCV per replenishment</p>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">CA Deadline — In-Station (days)</label>
                    <input type="number" name="ca_deadline_days_local" class="form-input w-full text-sm"
                        min="1" max="365"
                        value="{{ old('ca_deadline_days_local', $settings->ca_deadline_days_local ?? 60) }}">
                    <p class="text-[10px] text-gray-400 mt-1">Default: 60 days</p>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">CA Deadline — Travel (days)</label>
                    <input type="number" name="ca_deadline_days_travel" class="form-input w-full text-sm"
                        min="1" max="365"
                        value="{{ old('ca_deadline_days_travel', $settings->ca_deadline_days_travel ?? 90) }}">
                    <p class="text-[10px] text-gray-400 mt-1">Default: 90 days</p>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="mt-5 flex items-center gap-3">
    <button type="submit" class="btn bg-primary text-white px-8">Save Settings</button>
    <span class="text-xs text-gray-400">Changes apply immediately to new vouchers.</span>
</div>

</form>

@endsection

@push('inline-scripts')
<script>
function toggleSection(id, btn) {
    const body = document.getElementById(id);
    const icon = document.getElementById('icon-' + id);
    const isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : '';
    icon.style.transform = isOpen ? 'rotate(180deg)' : '';
}

const YEAR = '{{ date('Y') }}';
function updatePreview() {
    const dv  = document.querySelector('[name=voucher_prefix_dv]').value.trim()      || 'DV';
    const pcv = document.querySelector('[name=voucher_prefix_pcv]').value.trim()     || 'PCV';
    const pay = document.querySelector('[name=voucher_prefix_payroll]').value.trim() || 'PAY';
    const pad = parseInt(document.querySelector('[name=voucher_seq_padding]').value) || 3;
    const seq = String(1).padStart(pad, '0');
    document.getElementById('prefix-preview').textContent     = `${dv}-${YEAR}-${seq}`;
    document.getElementById('prefix-preview-pcv').textContent = `${pcv}-${YEAR}-${seq}`;
    document.getElementById('prefix-preview-pay').textContent = `${pay}-${YEAR}-${seq}`;
}
['voucher_prefix_dv','voucher_prefix_pcv','voucher_prefix_payroll','voucher_seq_padding'].forEach(name => {
    document.querySelector(`[name=${name}]`)?.addEventListener('input', updatePreview);
});
</script>
@endpush
