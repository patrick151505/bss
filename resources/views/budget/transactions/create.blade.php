@extends('layouts.vertical', [
    'title'         => 'New Voucher',
    'sub_title'     => 'Vouchers',
    'sub_title_url' => route('budget.transactions.index'),
    'tagline'       => 'Record a disbursement voucher against budget line items',
])

@section('content')

@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30">
    <ul class="text-sm text-danger space-y-1">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

@if(!$fy)
<div class="text-sm text-gray-400 p-4">No fiscal year found. <a href="{{ route('budget.index') }}" class="text-primary underline">Create one first →</a></div>
@else

<form method="POST" action="{{ route('budget.transactions.store') }}" id="voucher-form" onsubmit="return syncPayeeBeforeSubmit()">
@csrf
<input type="hidden" name="fiscal_year_id" value="{{ $fy->id }}">
<input type="hidden" name="tax_type" id="tax-type-hidden" value="{{ old('tax_type') }}">

<div class="grid grid-cols-10 gap-5 items-start">

{{-- LEFT 70% — Main form --}}
<div class="col-span-7 space-y-4">

    {{-- Voucher header card --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b dark:border-gray-700 flex items-center justify-between">
            <span class="font-semibold text-sm text-gray-800 dark:text-gray-100">Voucher Header</span>
            <span class="text-xs text-gray-400">{{ $fy->displayLabel() }}</span>
        </div>
        <div class="p-5 grid grid-cols-2 gap-4">

            {{-- Voucher Type --}}
            <div class="col-span-2">
                <label class="text-xs text-gray-400 mb-2 block">Voucher Type <span class="text-danger">*</span></label>
                <div class="flex gap-2">
                    @foreach(App\Models\BudgetTransaction::VOUCHER_TYPES as $val => $lbl)
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="voucher_type" value="{{ $val }}"
                            class="sr-only peer"
                            {{ old('voucher_type', $voucherType) === $val ? 'checked' : '' }}
                            onchange="onTypeChange('{{ $val }}')">
                        <span class="block text-center py-2 px-1 rounded-lg border text-xs font-semibold
                            border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400
                            peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary transition-all">
                            {{ $val }}<br><span class="font-normal text-gray-400 dark:text-gray-500 text-[10px]">{{ $lbl }}</span>
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Voucher No --}}
            <div>
                <label class="text-xs text-gray-400 mb-1 block">Voucher No. <span class="text-danger">*</span></label>
                <input type="text" name="voucher_no" id="voucher-no"
                    class="form-input w-full text-sm font-mono bg-white dark:bg-gray-700 @error('voucher_no') border-danger @enderror"
                    value="{{ old('voucher_no', $voucherNo) }}" required>
                <p class="text-[11px] text-gray-400 mt-0.5">Auto-generated — you can edit this.</p>
                @error('voucher_no')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Date --}}
            <div>
                <label class="text-xs text-gray-400 mb-1 block">Date <span class="text-danger">*</span></label>
                <input type="date" name="transaction_date"
                    class="form-input w-full text-sm @error('transaction_date') border-danger @enderror"
                    value="{{ old('transaction_date', date('Y-m-d')) }}" required>
            </div>

            {{-- Payee / Supplier --}}
            <div class="col-span-2">
                <label class="text-xs text-gray-400 mb-1 block">Payee / Supplier <span class="text-danger">*</span></label>
                <input type="hidden" name="supplier_id" id="supplier_id" value="{{ old('supplier_id') }}">
                <input type="hidden" name="payee" id="payee-hidden" value="{{ old('payee') }}">

                {{-- Not selected state --}}
                <div id="supplier-placeholder" class="{{ old('supplier_id') ? 'hidden' : '' }}">
                    <button type="button" onclick="openSupplierModal()"
                        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg border @error('payee') border-danger @else border-gray-200 dark:border-gray-600 @enderror bg-white dark:bg-gray-800 text-sm text-gray-400 hover:border-primary hover:text-primary transition-colors">
                        <i class="mgc_building_2_line text-base shrink-0"></i>
                        <span>Select a supplier / payee...</span>
                        <i class="mgc_search_line ml-auto shrink-0"></i>
                    </button>
                    @error('payee')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
                    <p id="payee-error" class="text-danger text-xs mt-1 hidden">Please select a supplier from the list.</p>
                </div>

                {{-- Selected state --}}
                <div id="supplier-info" class="{{ old('supplier_id') ? '' : 'hidden' }} p-3 rounded-lg bg-primary/5 border border-primary/20 dark:border-primary/30 text-xs">
                    <div class="flex items-center gap-2">
                        <i class="mgc_building_2_line text-primary text-base shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <div id="si-name" class="font-semibold text-gray-800 dark:text-gray-100 truncate"></div>
                            <div id="si-meta" class="text-gray-400 truncate"></div>
                        </div>
                        <button type="button" onclick="openSupplierModal()" class="shrink-0 text-primary hover:text-primary/70 text-[10px] font-medium px-2 py-0.5 border border-primary/30 rounded hover:bg-primary/10 transition-colors">
                            Change
                        </button>
                        <button type="button" onclick="clearSupplier()" class="shrink-0 text-gray-300 hover:text-danger ml-1">
                            <i class="mgc_close_line"></i>
                        </button>
                    </div>
                    <div id="si-address" class="hidden mt-1.5 pl-6 text-gray-500 leading-relaxed"></div>
                    <div id="si-tin" class="hidden mt-0.5 pl-6">TIN: <span class="font-mono text-gray-600 dark:text-gray-300"></span></div>
                </div>
            </div>

            {{-- Supplier picker modal --}}
            <div id="supplier-modal" class="fixed inset-0 z-[999] hidden flex items-center justify-center bg-black/40" onclick="if(event.target===this) closeSupplierModal()">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md mx-4">
                    <div class="px-5 py-3.5 border-b dark:border-gray-700 flex items-center justify-between">
                        <span class="font-semibold text-sm text-gray-800 dark:text-gray-100">Select Supplier</span>
                        <button type="button" onclick="closeSupplierModal()" class="text-gray-400 hover:text-gray-600"><i class="mgc_close_line text-lg"></i></button>
                    </div>
                    <div class="p-4">
                        <div class="relative mb-3">
                            <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="supplier-search"
                                class="form-input w-full text-sm pl-8"
                                placeholder="Search by name, TIN, or address..."
                                autocomplete="off"
                                oninput="onSupplierSearch(this.value)">
                        </div>
                        <div id="supplier-dropdown" class="max-h-64 overflow-y-auto rounded-lg border dark:border-gray-600 divide-y divide-gray-100 dark:divide-gray-700">
                            <div class="px-4 py-3 text-xs text-gray-400 text-center">Start typing to search suppliers</div>
                        </div>
                    </div>
                    <div class="px-5 py-3 border-t dark:border-gray-700 flex justify-between items-center">
                        <a href="{{ route('budget.suppliers.index') }}" target="_blank" class="text-xs text-primary hover:underline flex items-center gap-1">
                            <i class="mgc_add_line"></i> Add new supplier
                        </a>
                        <button type="button" onclick="closeSupplierModal()" class="px-4 py-1.5 text-xs rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Cancel</button>
                    </div>
                </div>
            </div>

            {{-- Description --}}
            <div class="col-span-2">
                <label class="text-xs text-gray-400 mb-1 block">Description / Purpose</label>
                <textarea name="description" rows="2"
                    class="form-input w-full text-sm resize-none"
                    placeholder="Brief explanation of what this payment is for">{{ old('description') }}</textarea>
            </div>

            {{-- Mode of Payment --}}
            <div>
                <label class="text-xs text-gray-400 mb-1 block">Mode of Payment <span class="text-danger">*</span></label>
                <select name="mode_of_payment" class="form-select w-full text-sm" onchange="onModeChange(this.value)" required>
                    @foreach(App\Models\BudgetTransaction::PAYMENT_MODES as $val => $lbl)
                    <option value="{{ $val }}" {{ old('mode_of_payment') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Gross Amount --}}
            <div>
                <label class="text-xs text-gray-400 mb-1 block">Gross Amount (₱) <span class="text-danger">*</span></label>
                <input type="number" name="amount" id="voucher-amount"
                    class="form-input w-full text-sm text-right tabular-nums @error('amount') border-danger @enderror"
                    step="0.01" min="0.01"
                    value="{{ old('amount') }}" placeholder="0.00"
                    oninput="updateNet(); recomputeTaxIfSupplierSelected()" required>
                @error('amount')<p class="text-danger text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Check / Bank Transfer fields --}}
            <div id="check-fields" class="col-span-2 grid grid-cols-3 gap-4 {{ in_array(old('mode_of_payment'), ['check','bank_transfer']) ? '' : 'hidden' }}">
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Check No.</label>
                    <input type="text" name="check_no" class="form-input w-full text-sm"
                        value="{{ old('check_no') }}" placeholder="e.g. 123456">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Check Date</label>
                    <input type="date" name="check_date" class="form-input w-full text-sm" value="{{ old('check_date') }}">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Bank Name</label>
                    <input type="text" name="bank_name" class="form-input w-full text-sm"
                        value="{{ old('bank_name') }}" placeholder="e.g. Land Bank">
                </div>
            </div>

            {{-- Cash / OR fields --}}
            <div id="or-fields" class="col-span-2 grid grid-cols-2 gap-4 {{ old('mode_of_payment') === 'cash' ? '' : 'hidden' }}">
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">OR Number</label>
                    <input type="text" name="or_number" class="form-input w-full text-sm"
                        value="{{ old('or_number') }}" placeholder="Official Receipt No.">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">OR Date</label>
                    <input type="date" name="or_date" class="form-input w-full text-sm" value="{{ old('or_date') }}">
                </div>
            </div>

            {{-- Tax Withheld + Net --}}
            <div>
                <label class="text-xs text-gray-400 mb-1 block">Tax Withheld (₱)</label>
                <input type="number" name="tax_withheld" id="tax-withheld"
                    class="form-input w-full text-sm text-right tabular-nums"
                    step="0.01" min="0"
                    value="{{ old('tax_withheld', 0) }}" placeholder="0.00"
                    oninput="updateNet()">
                {{-- BIR rule tag — shown after supplier is selected --}}
                <div id="tax-rule-tag" class="hidden mt-1.5 flex items-center gap-1.5 text-[10px] text-gray-500 dark:text-gray-400">
                    <i class="mgc_information_line text-warning text-xs shrink-0"></i>
                    <span id="tax-rule-label"></span>
                    <span class="text-gray-300 dark:text-gray-600">·</span>
                    <span id="tax-rule-rate" class="font-semibold text-warning"></span>
                    <button type="button" onclick="document.getElementById('tax-withheld').value=''; updateNet(); document.getElementById('tax-rule-tag').classList.add('hidden');"
                        class="ml-auto text-gray-300 hover:text-danger" title="Clear auto-fill">
                        <i class="mgc_close_line"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-end">
                <div class="w-full px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-700 border dark:border-gray-600">
                    <div class="text-xs text-gray-400 mb-0.5">Net Amount</div>
                    <div class="tabular-nums font-bold text-primary text-sm" id="net-amount">₱0.00</div>
                </div>
            </div>

        </div>
    </div>

    {{-- Charge lines card --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b dark:border-gray-700 flex items-center justify-between">
            <span class="font-semibold text-sm text-gray-800 dark:text-gray-100">Charge to Budget Line Items</span>
            <button type="button" onclick="addLine()"
                class="text-xs text-primary hover:text-primary/70 font-medium flex items-center gap-1">
                <i class="mgc_add_line"></i> Add line
            </button>
        </div>

        @error('lines')
        <div class="px-5 py-2 bg-danger/10 text-danger text-xs border-b dark:border-gray-700">{{ $message }}</div>
        @enderror

        <div id="charge-lines" class="divide-y divide-gray-100 dark:divide-gray-700/50">
            {{-- First charge line --}}
            <div class="charge-line p-4 grid grid-cols-12 gap-3 items-end" data-index="0">
                @include('budget.transactions._charge_line', ['index' => 0])
            </div>
        </div>

        {{-- Lines vs total footer --}}
        <div class="px-5 py-3 border-t dark:border-gray-700 flex items-center justify-between">
            <span class="text-gray-400 text-xs">Lines total must equal gross amount</span>
            <div class="flex items-center gap-4">
                <span class="text-xs text-gray-400">Lines: <span class="tabular-nums font-semibold text-gray-700 dark:text-gray-200" id="lines-total">₱0.00</span></span>
                <span class="text-xs font-medium" id="lines-match-indicator"></span>
            </div>
        </div>
    </div>

</div>

{{-- RIGHT 30% — Info sidebar --}}
<div class="col-span-3 space-y-3">

    <button type="submit"
        class="w-full py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 transition-colors flex items-center justify-center gap-2">
        <i class="mgc_save_line"></i> Save as Draft
    </button>
    <a href="{{ route('budget.transactions.index', ['fy' => $fy->id]) }}"
        class="w-full py-2.5 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center justify-center gap-2 block text-center">
        Cancel
    </a>

    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl p-4 space-y-3 text-xs text-gray-500 dark:text-gray-400 mt-2">
        <p class="font-semibold text-gray-600 dark:text-gray-300 text-sm">Notes</p>
        <div class="flex gap-2">
            <i class="mgc_information_line text-primary shrink-0 mt-0.5"></i>
            <span>Voucher is saved as <strong>Draft</strong> — no balance deducted yet.</span>
        </div>
        <div class="flex gap-2">
            <i class="mgc_information_line text-primary shrink-0 mt-0.5"></i>
            <span>Attach physical approval document before marking <strong>Approved</strong>.</span>
        </div>
        <div class="flex gap-2">
            <i class="mgc_information_line text-primary shrink-0 mt-0.5"></i>
            <span>One voucher can charge <strong>multiple line items</strong>.</span>
        </div>
    </div>

</div>

</div>{{-- end grid --}}
</form>

@endif
@endsection

@push('inline-scripts')
<script>
const PROGRAMS_DATA    = {!! $programsJson !!};
const LINE_ITEMS_DATA  = {!! $lineItemsJson !!};
const SUPPLIER_SEARCH_URL = '{{ route('budget.suppliers.search') }}';

// ── Supplier picker ────────────────────────────────────
let supplierTimer = null;

function openSupplierModal() {
    document.getElementById('supplier-modal').classList.remove('hidden');
    setTimeout(() => {
        const inp = document.getElementById('supplier-search');
        inp.value = '';
        inp.focus();
        onSupplierSearch('');
    }, 50);
}

function closeSupplierModal() {
    document.getElementById('supplier-modal').classList.add('hidden');
}

async function onSupplierSearch(val) {
    clearTimeout(supplierTimer);
    supplierTimer = setTimeout(async () => {
        const res  = await fetch(SUPPLIER_SEARCH_URL + '?q=' + encodeURIComponent(val));
        const list = await res.json();
        renderDropdown(list);
    }, 200);
}

function renderDropdown(list) {
    const dd = document.getElementById('supplier-dropdown');
    if (!list.length) {
        dd.innerHTML = '<div class="px-4 py-4 text-gray-400 text-xs text-center">No suppliers found.<br><a href="{{ route('budget.suppliers.index') }}" target="_blank" class="text-primary hover:underline">Add a new supplier →</a></div>';
        return;
    }
    dd.innerHTML = list.map(s => `
        <div class="px-4 py-2.5 hover:bg-primary/5 dark:hover:bg-primary/10 cursor-pointer transition-colors"
             onclick='selectSupplier(${JSON.stringify(s).replace(/'/g, "&#39;")})'>
            <div class="font-medium text-sm text-gray-800 dark:text-gray-100">${s.name}</div>
            <div class="flex gap-3 mt-0.5">
                ${s.tin ? `<span class="text-[10px] text-gray-400 font-mono">TIN: ${s.tin}</span>` : ''}
                ${s.line_of_business ? `<span class="text-[10px] text-gray-400">${s.line_of_business}</span>` : ''}
            </div>
            ${s.address ? `<div class="text-[10px] text-gray-400 truncate mt-0.5">${s.address}</div>` : ''}
        </div>`).join('');
}

function selectSupplier(s) {
    currentSupplier = s;
    document.getElementById('payee-hidden').value = s.name;
    document.getElementById('supplier_id').value  = s.id;
    closeSupplierModal();
    showSupplierCard(s);
    autoFillTax(s);
    document.getElementById('payee-error').classList.add('hidden');
}

function autoFillTax(s) {
    const gross = parseFloat(document.getElementById('voucher-amount').value) || 0;
    let rate = 0, label = '', rateLabel = '';

    // BIR withholding tax rules for government payments
    let taxType = '';
    if (s.vat_type === 'non_vat') {
        rate = 0.03; taxType = 'nv_3';
        label = 'Sales to Govt – Non-VAT (NV)';
        rateLabel = '3%';
    } else if (s.vat_type === 'vat') {
        rate = 0; taxType = 'vat_0';
        label = 'Sales to Govt – VAT Zero-Rated';
        rateLabel = '0%';
    } else if (s.vat_type === 'exempt') {
        if (s.provides === 'goods') {
            rate = 0.01; taxType = 'ewt_goods_1';
            label = 'EWT – Goods (VAT-Exempt)';
            rateLabel = '1%';
        } else if (s.provides === 'services') {
            rate = 0.02; taxType = 'ewt_services_2';
            label = 'EWT – Services (VAT-Exempt)';
            rateLabel = '2%';
        } else {
            rate = 0.01; taxType = 'ewt_both_1';
            label = 'EWT – Goods & Services (VAT-Exempt)';
            rateLabel = '1%';
        }
    }
    document.getElementById('tax-type-hidden').value = taxType;

    const tax = parseFloat((gross * rate).toFixed(2));
    document.getElementById('tax-withheld').value = tax > 0 ? tax.toFixed(2) : '0.00';
    updateNet();

    const tagEl   = document.getElementById('tax-rule-tag');
    const labelEl = document.getElementById('tax-rule-label');
    const rateEl  = document.getElementById('tax-rule-rate');
    if (label) {
        labelEl.textContent = label;
        rateEl.textContent  = rateLabel + ' of gross';
        tagEl.classList.remove('hidden');
    } else {
        tagEl.classList.add('hidden');
    }
}

function showSupplierCard(s) {
    document.getElementById('supplier-placeholder').classList.add('hidden');
    document.getElementById('supplier-info').classList.remove('hidden');
    document.getElementById('si-name').textContent = s.name;

    const meta = [
        s.business_type && s.business_type !== 'na' ? (s.business_type === 'individual' ? 'Individual' : 'Corporation') : null,
        s.vat_type === 'vat' ? 'VAT-Registered' : (s.vat_type === 'exempt' ? 'VAT-Exempt' : 'Non-VAT'),
        s.provides ? (s.provides === 'goods' ? 'Goods' : s.provides === 'services' ? 'Services' : 'Goods & Services') : null,
    ].filter(Boolean).join(' · ');
    document.getElementById('si-meta').textContent = meta;

    const addrEl = document.getElementById('si-address');
    const addr   = [s.address, s.zip_code].filter(Boolean).join(', ');
    addrEl.textContent = addr;
    addr ? addrEl.classList.remove('hidden') : addrEl.classList.add('hidden');

    const tinEl = document.getElementById('si-tin');
    tinEl.querySelector('span').textContent = s.tin || '';
    s.tin ? tinEl.classList.remove('hidden') : tinEl.classList.add('hidden');
}

function clearSupplier() {
    document.getElementById('supplier_id').value  = '';
    document.getElementById('payee-hidden').value = '';
    document.getElementById('supplier-info').classList.add('hidden');
    document.getElementById('supplier-placeholder').classList.remove('hidden');
    document.getElementById('tax-rule-tag').classList.add('hidden');
    currentSupplier = null;
}

let currentSupplier = null;

function recomputeTaxIfSupplierSelected() {
    if (currentSupplier) autoFillTax(currentSupplier);
}

function syncPayeeBeforeSubmit() {
    const supplierId = document.getElementById('supplier_id').value;
    if (!supplierId) {
        const errEl = document.getElementById('payee-error');
        errEl.classList.remove('hidden');
        openSupplierModal();
        return false;
    }
    return true;
}

let lineCount = 1;

function fmt(n) {
    return '₱' + (parseFloat(n)||0).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function updateNet() {
    const gross = parseFloat(document.getElementById('voucher-amount').value) || 0;
    const tax   = parseFloat(document.getElementById('tax-withheld').value) || 0;
    document.getElementById('net-amount').textContent = fmt(gross - tax);
    updateLinesTotal();
}

function updateLinesTotal() {
    let total = 0;
    document.querySelectorAll('.line-amount-input').forEach(i => total += parseFloat(i.value)||0);
    const gross = parseFloat(document.getElementById('voucher-amount').value) || 0;
    document.getElementById('lines-total').textContent = fmt(total);
    const ind = document.getElementById('lines-match-indicator');
    if (gross === 0) { ind.textContent = ''; return; }
    if (Math.abs(total - gross) < 0.01) {
        ind.className = 'text-xs font-medium text-success';
        ind.textContent = '✓ Matches';
    } else {
        ind.className = 'text-xs font-medium text-danger';
        ind.textContent = `Short ₱${(gross - total).toLocaleString('en-PH', {minimumFractionDigits:2})}`;
    }
}

function onTypeChange(type) {
    const el    = document.getElementById('voucher-no');
    const match = el.value.match(/^(DV|PCV|PAY|VCH)-(\d+)-(\d+)$/);
    const prefix = {DV:'DV', PCV:'PCV', Payroll:'PAY'}[type] || 'VCH';
    if (match) el.value = `${prefix}-${match[2]}-${match[3]}`;
}

function onModeChange(val) {
    document.getElementById('check-fields').classList.toggle('hidden', val !== 'check' && val !== 'bank_transfer');
    document.getElementById('or-fields').classList.toggle('hidden', val !== 'cash');
}

function buildLineItemOptions(programId) {
    const byClass = LINE_ITEMS_DATA[programId];
    if (!byClass || !Object.keys(byClass).length) return '<option value="">— no line items for this program —</option>';
    let html = '<option value="">Select line item</option>';
    Object.entries(byClass).forEach(([cls, items]) => {
        html += `<optgroup label="${cls}">`;
        items.forEach(i => {
            const code = i.object_code ? `${i.object_code} — ` : '';
            const bal  = fmt(i.balance);
            html += `<option value="${i.id}" data-balance="${i.balance}" data-name="${i.name}">${code}${i.name} (bal: ${bal})</option>`;
        });
        html += '</optgroup>';
    });
    return html;
}

function getLineBalance(lineEl) {
    const sel = lineEl.querySelector('.line-select');
    const opt = sel?.options[sel.selectedIndex];
    return opt ? parseFloat(opt.dataset.balance || 0) : null;
}

function checkLineBalance(amountInput) {
    const lineEl = amountInput.closest('.charge-line');
    const balance = getLineBalance(lineEl);
    let hint = lineEl.querySelector('.balance-hint');
    if (!hint) {
        hint = document.createElement('p');
        hint.className = 'balance-hint text-[11px] mt-0.5';
        amountInput.parentNode.appendChild(hint);
    }
    if (balance === null) { hint.textContent = ''; return; }
    const amt = parseFloat(amountInput.value) || 0;
    if (amt > balance + 0.01) {
        hint.className = 'balance-hint text-[11px] mt-0.5 text-danger';
        hint.textContent = `Exceeds balance ₱${balance.toLocaleString('en-PH', {minimumFractionDigits:2})}`;
    } else {
        hint.className = 'balance-hint text-[11px] mt-0.5 text-gray-400';
        hint.textContent = `Balance: ₱${balance.toLocaleString('en-PH', {minimumFractionDigits:2})}`;
    }
}

function progOptions() {
    let html = '<option value="">Select program</option>';
    PROGRAMS_DATA.forEach(p => { html += `<option value="${p.id}">${p.name}</option>`; });
    return html;
}

function buildLineHTML(idx) {
    return `
        <div class="col-span-4">
            <label class="text-xs text-gray-400 mb-1 block">Program</label>
            <select name="lines[${idx}][program_id_ui]" class="form-select w-full text-sm prog-select"
                onchange="onProgChange(this, ${idx})">${progOptions()}</select>
        </div>
        <div class="col-span-5">
            <label class="text-xs text-gray-400 mb-1 block">Line Item <span class="text-danger">*</span></label>
            <select name="lines[${idx}][line_item_id]" class="form-select w-full text-sm line-select" required>
                <option value="">— pick a program first —</option>
            </select>
        </div>
        <div class="col-span-2">
            <label class="text-xs text-gray-400 mb-1 block">Amount <span class="text-danger">*</span></label>
            <input type="number" name="lines[${idx}][amount]"
                class="form-input w-full text-sm text-right tabular-nums line-amount-input"
                step="0.01" min="0.01" placeholder="0.00" oninput="updateLinesTotal(); checkLineBalance(this)" required>
        </div>
        <div class="col-span-1 flex items-end justify-center pb-0.5">
            <button type="button" onclick="removeLine(this)"
                class="p-1.5 text-gray-400 hover:text-danger rounded transition-colors">
                <i class="mgc_delete_line"></i>
            </button>
        </div>`;
}

function addLine() {
    const idx = lineCount++;
    const div = document.createElement('div');
    div.className  = 'charge-line p-4 grid grid-cols-12 gap-3 items-end border-t dark:border-gray-700/50';
    div.dataset.index = idx;
    div.innerHTML  = buildLineHTML(idx);
    document.getElementById('charge-lines').appendChild(div);
    // wire prog change for this new line
    div.querySelector('.prog-select').addEventListener('change', e => onProgChange(e.target, idx));
}

function onProgChange(sel, idx) {
    const lineEl     = sel.closest('.charge-line');
    const lineSelect = lineEl.querySelector('.line-select');
    lineSelect.innerHTML = buildLineItemOptions(sel.value);
    lineSelect.addEventListener('change', () => {
        const amtInput = lineEl.querySelector('.line-amount-input');
        if (amtInput) checkLineBalance(amtInput);
    });
}

function removeLine(btn) {
    const lines = document.querySelectorAll('.charge-line');
    if (lines.length <= 1) return;
    btn.closest('.charge-line').remove();
    updateLinesTotal();
}

document.addEventListener('DOMContentLoaded', () => {
    updateNet();
    // Wire up first line's prog-select (rendered by Blade partial)
    const firstProg = document.querySelector('.prog-select');
    if (firstProg) {
        firstProg.addEventListener('change', e => onProgChange(e.target, 0));
    }

    // On page load: if old supplier_id exists (returning from validation error), restore supplier card
    const oldSupplierId = document.getElementById('supplier_id').value;
    if (oldSupplierId) {
        fetch(SUPPLIER_SEARCH_URL + '?q=')
            .then(r => r.json())
            .then(list => {
                const found = list.find(s => s.id == oldSupplierId);
                if (found) showSupplierCard(found);
            });
    }
});
</script>
@endpush
