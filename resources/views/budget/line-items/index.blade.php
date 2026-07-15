@extends('layouts.vertical', [
    'title'         => 'Line Items',
    'sub_title'     => 'Budget',
    'sub_title_url' => route('budget.index'),
    'tagline'       => 'Define named expense lines under each Program',
])

@section('content')

{{-- Toast --}}
<div id="toast" class="fixed top-5 right-5 z-[9999] hidden">
    <div id="toast-inner" class="flex items-center gap-2 px-4 py-2.5 rounded-lg shadow-lg text-xs font-medium min-w-[220px]"></div>
</div>

@if(session('success'))
<script>document.addEventListener('DOMContentLoaded', () => showToast(@json(session('success')), 'success'));</script>
@endif
@if(session('error'))
<script>document.addEventListener('DOMContentLoaded', () => showToast(@json(session('error')), 'error'));</script>
@endif

{{-- FY Selector --}}
<div class="flex items-center gap-3 mb-5">
    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Fiscal Year:</label>
    <form method="GET" action="{{ route('budget.line-items.index') }}">
        <select name="fy" class="form-select w-36 text-sm" onchange="this.form.submit()">
            @foreach($fiscalYears as $fyOpt)
                <option value="{{ $fyOpt->id }}" {{ $fyOpt->id == $fy?->id ? 'selected' : '' }}>
                    {{ $fyOpt->displayLabel() }}{{ $fyOpt->is_active ? ' ★' : '' }}
                </option>
            @endforeach
        </select>
    </form>
</div>

@if(!$fy)
<p class="text-sm text-gray-400">No fiscal year found. <a href="{{ route('budget.index') }}" class="text-primary underline">Create one →</a></p>
@else

{{-- Grand total strip --}}
@php
    $totals = ['PS' => 0, 'MOOE' => 0, 'CO' => 0];
    foreach ($lineItems as $items) {
        foreach ($items as $item) $totals[$item->object_class] += (float)$item->appropriation;
    }
    $grand = array_sum($totals);
@endphp
<div class="flex items-center gap-0 mb-5 bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden divide-x dark:divide-gray-700">
    @foreach([
        'PS'   => ['Personal Services',  'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'],
        'MOOE' => ['MOOE',               'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'],
        'CO'   => ['Capital Outlay',     'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'],
    ] as $cls => [$lbl, $badge])
    <div class="flex items-center gap-2 px-4 py-3 flex-1">
        <span class="text-xs font-semibold px-1.5 py-0.5 rounded {{ $badge }}">{{ $cls }}</span>
        <div>
            <div class="text-xs text-gray-400">{{ $lbl }}</div>
            <div class="tabular-nums font-semibold text-sm text-gray-700 dark:text-gray-200" id="summary-{{ $cls }}">₱{{ number_format($totals[$cls], 2) }}</div>
        </div>
    </div>
    @endforeach
    <div class="flex items-center gap-2 px-4 py-3">
        <div>
            <div class="text-xs text-primary/70">Grand Total</div>
            <div class="tabular-nums font-bold text-sm text-primary" id="summary-grand">₱{{ number_format($grand, 2) }}</div>
        </div>
    </div>
</div>

{{-- Main layout: 70% cards + 30% sidebar --}}
<div class="grid grid-cols-10 gap-5 items-start">

{{-- LEFT 70% — Program cards --}}
<div class="col-span-7 space-y-3">
@forelse($programs as $prog)
@php
    $progItems = $lineItems->get($prog->id) ?? collect();
    $progTotal = $progItems->sum('appropriation');
@endphp

<div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden" id="prog-card-{{ $prog->id }}">

    {{-- Program row --}}
    <div class="flex items-center justify-between px-4 py-2.5 border-b dark:border-gray-700 cursor-pointer select-none"
        onclick="toggleCard({{ $prog->id }}, this)">
        <div class="flex items-center gap-2">
            <i class="mgc_down_line text-gray-400 text-base transition-transform duration-200" id="prog-chevron-{{ $prog->id }}"></i>
            <span class="font-semibold text-sm text-gray-800 dark:text-gray-100">{{ $prog->name }}</span>
            @if($prog->isMandatory())
            <span class="text-xs px-1.5 py-0.5 rounded bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400">Mandatory</span>
            @endif
        </div>
        <div class="flex items-center gap-4 text-xs tabular-nums">
            @foreach([
                'PS'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                'MOOE' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                'CO'   => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
            ] as $hCls => $hBadge)
            @php $hTotal = $progItems->where('object_class', $hCls)->sum('appropriation'); @endphp
            <span class="flex items-center gap-1.5">
                <span class="px-1.5 py-0.5 rounded text-xs font-semibold {{ $hBadge }}">{{ $hCls }}</span>
                <span id="prog-cls-{{ $prog->id }}-{{ $hCls }}" class="tabular-nums text-gray-600 dark:text-gray-300">₱{{ number_format($hTotal, 2) }}</span>
            </span>
            @endforeach
            <span class="font-semibold text-gray-700 dark:text-gray-200 border-l dark:border-gray-600 pl-4" id="prog-total-{{ $prog->id }}">₱{{ number_format($progTotal, 2) }}</span>
        </div>
    </div>

    {{-- Collapsible body --}}
    <div id="prog-body-{{ $prog->id }}">

    {{-- PS / MOOE / CO sections --}}
    @foreach(['PS' => 'Personal Services', 'MOOE' => 'Maintenance & Other Operating', 'CO' => 'Capital Outlay'] as $cls => $clsLabel)
    @php
        $clsItems = $progItems->where('object_class', $cls)->sortBy('sort_order')->values();
        $clsTotal = $clsItems->sum('appropriation');
        $clsBadge = match($cls) {
            'PS'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
            'MOOE' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
            'CO'   => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
        };
    @endphp

    <div class="border-b dark:border-gray-700/50 last:border-b-0">

        {{-- Object class header --}}
        <div class="flex items-center justify-between px-4 py-1.5 bg-gray-50/60 dark:bg-gray-700/20 cursor-pointer select-none"
            onclick="toggleCls({{ $prog->id }}, '{{ $cls }}', this)">
            <div class="flex items-center gap-2">
                <i class="mgc_down_line text-gray-400 text-sm transition-transform duration-200" id="cls-chevron-{{ $prog->id }}-{{ $cls }}" style="transform:rotate(-90deg)"></i>
                <span class="text-xs font-semibold px-1.5 py-0.5 rounded {{ $clsBadge }}">{{ $cls }}</span>
                <span class="text-xs text-gray-400">{{ $clsLabel }}</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs tabular-nums text-gray-400" id="cls-total-{{ $prog->id }}-{{ $cls }}">
                    {{ $clsTotal > 0 ? '₱'.number_format($clsTotal, 2) : '' }}
                </span>
                <button type="button"
                    onclick="event.stopPropagation(); seedDefaults({{ $prog->id }}, '{{ $cls }}')"
                    class="text-xs text-gray-400 hover:text-primary transition-colors">
                    + seed
                </button>
                <button type="button"
                    onclick="event.stopPropagation(); openAdd({{ $prog->id }}, '{{ $cls }}')"
                    class="text-xs text-primary hover:text-primary/70 font-medium transition-colors">
                    + Add item
                </button>
            </div>
        </div>

        {{-- Line items (collapsed by default) --}}
        <div id="items-{{ $prog->id }}-{{ $cls }}" class="hidden">
            @foreach($clsItems as $item)
            @include('budget.line-items._row', ['item' => $item])
            @endforeach
        </div>

    </div>
    @endforeach

    </div>{{-- end prog-body --}}

</div>
@empty
<p class="text-sm text-gray-400">No active programs. <a href="{{ route('budget.programs.index') }}" class="text-primary underline">Manage Programs →</a></p>
@endforelse
</div>{{-- end left --}}

{{-- RIGHT 30% — Sidebar --}}
<div class="col-span-3 space-y-4">

    {{-- New Voucher button --}}
    <a href="{{ route('budget.transactions.create', ['fy' => $fy->id]) }}"
        class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 transition-colors">
        <i class="mgc_add_line text-base"></i>
        New Voucher
    </a>

    {{-- Recent transactions --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-4 py-2.5 border-b dark:border-gray-700 flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Recent Vouchers</span>
            <a href="{{ route('budget.transactions.index') }}" class="text-xs text-primary hover:underline">View all →</a>
        </div>
        @php
            $recentTx = \App\Models\BudgetTransaction::where('fiscal_year_id', $fy->id)
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();
        @endphp
        @if($recentTx->isEmpty())
        <div class="px-4 py-5 text-center text-xs text-gray-400 italic">
            No vouchers yet for {{ $fy->displayLabel() }}.
        </div>
        @else
        <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
            @foreach($recentTx as $tx)
            @php
                $txStatus = App\Models\BudgetTransaction::STATUSES[$tx->status] ?? ['label' => $tx->status, 'color' => ''];
            @endphp
            <a href="{{ route('budget.transactions.show', $tx) }}"
                class="flex items-center gap-2 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                {{-- Type badge --}}
                <span class="shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 w-10 text-center">
                    {{ $tx->voucher_type }}
                </span>
                {{-- Voucher no + payee --}}
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-mono font-medium text-primary truncate">{{ $tx->voucher_no ?? '—' }}</div>
                    <div class="text-[11px] text-gray-400 truncate">{{ $tx->payee ?? '—' }}</div>
                </div>
                {{-- Amount + status --}}
                <div class="shrink-0 text-right">
                    <div class="text-xs tabular-nums font-semibold text-gray-700 dark:text-gray-200">₱{{ number_format($tx->amount, 2) }}</div>
                    <span class="inline-block text-[10px] font-medium px-1.5 py-0.5 rounded {{ $txStatus['color'] }}">{{ $txStatus['label'] }}</span>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Info card --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-4 py-2.5 border-b dark:border-gray-700">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">How it works</span>
        </div>
        <div class="p-4 space-y-3 text-xs text-gray-500 dark:text-gray-400">
            <div class="flex gap-2">
                <i class="mgc_mouse_line text-primary shrink-0 mt-0.5"></i>
                <span>Click a program header to collapse or expand it.</span>
            </div>
            <div class="flex gap-2">
                <i class="mgc_magic_1_line text-primary shrink-0 mt-0.5"></i>
                <span><strong class="text-gray-600 dark:text-gray-300">+ seed</strong> loads standard COA names with ₱0 — fill in the amounts after.</span>
            </div>
            <div class="flex gap-2">
                <i class="mgc_add_line text-primary shrink-0 mt-0.5"></i>
                <span><strong class="text-gray-600 dark:text-gray-300">+ Add item</strong> creates a custom line under that object class.</span>
            </div>
            <div class="flex gap-2">
                <i class="mgc_delete_line text-primary shrink-0 mt-0.5"></i>
                <span>Lines with existing vouchers or cash advances cannot be deleted.</span>
            </div>
        </div>
    </div>

    {{-- Progress bar legend --}}
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-4 py-2.5 border-b dark:border-gray-700">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Budget Bar Colors</span>
        </div>
        <div class="p-4 space-y-2.5 text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center gap-3">
                <div class="w-16 h-2 rounded-full bg-success shrink-0"></div>
                <span><strong class="text-gray-600 dark:text-gray-300">0–50%</strong> — Safe, plenty remaining</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-16 h-2 rounded-full bg-warning shrink-0"></div>
                <span><strong class="text-gray-600 dark:text-gray-300">51–80%</strong> — Moderate, over half used</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-16 h-2 rounded-full bg-orange-400 shrink-0"></div>
                <span><strong class="text-gray-600 dark:text-gray-300">81–99%</strong> — Nearly exhausted</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-16 h-2 rounded-full bg-danger shrink-0"></div>
                <span><strong class="text-gray-600 dark:text-gray-300">100%+</strong> — At limit or overdrawn</span>
            </div>
            <p class="pt-1 text-[10px] text-gray-400 dark:text-gray-500 leading-relaxed">Only <strong class="text-gray-500">approved</strong> and <strong class="text-gray-500">paid</strong> vouchers count toward disbursements. Drafts do not affect the balance.</p>
        </div>
    </div>

</div>{{-- end sidebar --}}

</div>{{-- end grid layout --}}

@endif

{{-- Add Modal --}}
<div id="add-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm mx-4">
        <div class="flex justify-between items-center px-5 py-3 border-b dark:border-gray-700">
            <h3 class="font-semibold text-sm text-gray-800 dark:text-white" id="add-modal-title">Add Line Item</h3>
            <button onclick="closeAdd()" class="text-gray-400 hover:text-gray-600"><i class="mgc_close_line"></i></button>
        </div>
        <div class="p-5 space-y-3">
            <div class="flex gap-3">
                <div class="w-28 shrink-0">
                    <label class="text-xs text-gray-400 mb-1 block">Object Code</label>
                    <input type="text" id="add-code" class="form-input w-full text-sm font-mono" placeholder="50203010" maxlength="20">
                </div>
                <div class="flex-1">
                    <label class="text-xs text-gray-400 mb-1 block">Name <span class="text-danger">*</span></label>
                    <input type="text" id="add-name" class="form-input w-full text-sm" placeholder="e.g. Office Supplies">
                    <p id="add-name-error" class="text-danger text-xs mt-1 hidden"></p>
                </div>
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">Appropriation (₱) <span class="text-danger">*</span></label>
                <input type="number" id="add-amount" class="form-input w-full text-sm text-right tabular-nums" step="0.01" min="0" placeholder="0.00">
            </div>
        </div>
        <div class="flex justify-end gap-2 px-5 py-3 border-t dark:border-gray-700">
            <button type="button" onclick="closeAdd()" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</button>
            <button type="button" onclick="submitAdd()" id="add-btn" class="btn btn-sm bg-primary text-white">Add</button>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm mx-4">
        <div class="flex justify-between items-center px-5 py-3 border-b dark:border-gray-700">
            <h3 class="font-semibold text-sm text-gray-800 dark:text-white">Edit Line Item</h3>
            <button onclick="closeEdit()" class="text-gray-400 hover:text-gray-600"><i class="mgc_close_line"></i></button>
        </div>
        <div class="p-5 space-y-3">
            <div class="flex gap-3">
                <div class="w-28 shrink-0">
                    <label class="text-xs text-gray-400 mb-1 block">Object Code</label>
                    <input type="text" id="edit-code" class="form-input w-full text-sm font-mono" placeholder="50203010" maxlength="20">
                </div>
                <div class="flex-1">
                    <label class="text-xs text-gray-400 mb-1 block">Name <span class="text-danger">*</span></label>
                    <input type="text" id="edit-name" class="form-input w-full text-sm">
                    <p id="edit-name-error" class="text-danger text-xs mt-1 hidden"></p>
                </div>
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">Appropriation (₱) <span class="text-danger">*</span></label>
                <input type="number" id="edit-amount" class="form-input w-full text-sm text-right tabular-nums" step="0.01" min="0">
            </div>
        </div>
        <div class="flex justify-end gap-2 px-5 py-3 border-t dark:border-gray-700">
            <button type="button" onclick="closeEdit()" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</button>
            <button type="button" onclick="submitEdit()" id="edit-btn" class="btn btn-sm bg-primary text-white">Update</button>
        </div>
    </div>
</div>

@endsection

@push('inline-scripts')
<script>
const FY_ID          = {{ $fy?->id ?? 'null' }};
const CSRF           = '{{ csrf_token() }}';
const STORE_URL      = '{{ route('budget.line-items.store') }}';
const SEED_URL       = '{{ route('budget.line-items.seed-defaults') }}';
const UPDATE_BASE    = '{{ url('budget/line-items') }}';

// ── Toast ──────────────────────────────────────────────
let toastTimer;
function showToast(msg, type = 'success') {
    const el = document.getElementById('toast');
    const inn = document.getElementById('toast-inner');
    inn.className = 'flex items-center gap-2 px-4 py-2.5 rounded-lg shadow-lg text-xs font-medium min-w-[220px] ' +
        (type === 'success' ? 'bg-success/10 border border-success/30 text-success' : 'bg-danger/10 border border-danger/30 text-danger');
    inn.innerHTML = `<i class="mgc_${type==='success'?'check_circle':'alert'}_line text-base shrink-0"></i><span>${msg}</span>`;
    el.classList.remove('hidden');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.add('hidden'), 3000);
}

// ── Format ─────────────────────────────────────────────
const fmt = n => '₱' + parseFloat(n||0).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Recalc totals ──────────────────────────────────────
function recalcAll() {
    const totals = {PS:0, MOOE:0, CO:0};

    document.querySelectorAll('[id^="prog-card-"]').forEach(card => {
        const pid = card.id.replace('prog-card-','');
        let progTotal = 0;

        ['PS','MOOE','CO'].forEach(cls => {
            let clsTotal = 0;
            card.querySelectorAll(`[data-prog="${pid}"][data-cls="${cls}"]`).forEach(row => {
                clsTotal += parseFloat(row.dataset.amount) || 0;
            });
            totals[cls] += clsTotal;
            progTotal   += clsTotal;
            // class section subtotal
            const clsEl = document.getElementById(`cls-total-${pid}-${cls}`);
            if (clsEl) clsEl.textContent = clsTotal > 0 ? fmt(clsTotal) : '';
            // header breakdown
            const hEl = document.getElementById(`prog-cls-${pid}-${cls}`);
            if (hEl) hEl.textContent = fmt(clsTotal);
        });

        const progEl = document.getElementById(`prog-total-${pid}`);
        if (progEl) progEl.textContent = fmt(progTotal);
    });

    ['PS','MOOE','CO'].forEach(cls => {
        const el = document.getElementById('summary-' + cls);
        if (el) el.textContent = fmt(totals[cls]);
    });
    const ge = document.getElementById('summary-grand');
    if (ge) ge.textContent = fmt(totals.PS + totals.MOOE + totals.CO);
}

// ── Build row ──────────────────────────────────────────
function buildRow(item) {
    const div = document.createElement('div');
    div.id             = 'item-row-' + item.id;
    div.dataset.prog   = item.program_id;
    div.dataset.cls    = item.object_class;
    div.dataset.amount = item.appropriation;
    div.className = 'flex items-center gap-2 px-4 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/20 group border-b border-gray-100 dark:border-gray-700/30 last:border-b-0';
    div.innerHTML = `
        <span class="text-gray-300 dark:text-gray-600 shrink-0 select-none">└─</span>
        <span class="text-xs font-mono text-gray-400  shrink-0">${escHtml(item.object_code||'')}</span>
        <span class="flex-1 text-gray-700 dark:text-gray-300">${escHtml(item.name)}</span>
        <span class="tabular-nums text-gray-700 dark:text-gray-200 font-medium">${fmt(item.appropriation)}</span>
        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button type="button" onclick='openEdit(${item.id},${JSON.stringify(item.name)},${item.appropriation},${JSON.stringify(item.object_code||"")})'
                class="p-1 text-gray-400 hover:text-primary rounded"><i class="mgc_edit_line"></i></button>
            <button type="button" onclick='confirmDelete(${item.id},${JSON.stringify(item.name)})'
                class="p-1 text-gray-400 hover:text-danger rounded"><i class="mgc_delete_line"></i></button>
        </div>`;
    return div;
}

// ── Add ────────────────────────────────────────────────
let addProgId = null, addCls = null;

function openAdd(progId, cls) {
    addProgId = progId; addCls = cls;
    document.getElementById('add-modal-title').textContent = `Add ${cls} Line Item`;
    document.getElementById('add-name').value   = '';
    document.getElementById('add-code').value   = '';
    document.getElementById('add-amount').value = '';
    document.getElementById('add-name-error').classList.add('hidden');
    const m = document.getElementById('add-modal');
    m.classList.remove('hidden'); m.classList.add('flex');
    setTimeout(() => document.getElementById('add-name').focus(), 50);
}
function closeAdd() {
    const m = document.getElementById('add-modal');
    m.classList.add('hidden'); m.classList.remove('flex');
}
async function submitAdd() {
    const name   = document.getElementById('add-name').value.trim();
    const code   = document.getElementById('add-code').value.trim();
    const amount = document.getElementById('add-amount').value;
    const errEl  = document.getElementById('add-name-error');
    if (!name) { errEl.textContent = 'Name is required.'; errEl.classList.remove('hidden'); return; }
    errEl.classList.add('hidden');

    const btn = document.getElementById('add-btn');
    btn.disabled = true; btn.textContent = 'Adding…';
    try {
        const res  = await fetch(STORE_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({fiscal_year_id:FY_ID, program_id:addProgId, object_class:addCls, object_code:code, name, appropriation:amount||0}),
        });
        const data = await res.json();
        if (!res.ok) { errEl.textContent = data.error||'Failed.'; errEl.classList.remove('hidden'); return; }

        const container = document.getElementById(`items-${addProgId}-${addCls}`);
        container.appendChild(buildRow(data.item));
        recalcAll();
        closeAdd();
        showToast(data.message);
    } catch(e) { showToast('Network error.','error'); }
    finally { btn.disabled=false; btn.textContent='Add'; }
}

// ── Edit ───────────────────────────────────────────────
let editId = null;

function openEdit(id, name, amount, code) {
    editId = id;
    document.getElementById('edit-name').value   = name;
    document.getElementById('edit-code').value   = code||'';
    document.getElementById('edit-amount').value = amount;
    document.getElementById('edit-name-error').classList.add('hidden');
    const m = document.getElementById('edit-modal');
    m.classList.remove('hidden'); m.classList.add('flex');
    setTimeout(() => document.getElementById('edit-name').focus(), 50);
}
function closeEdit() {
    const m = document.getElementById('edit-modal');
    m.classList.add('hidden'); m.classList.remove('flex');
}
async function submitEdit() {
    const name   = document.getElementById('edit-name').value.trim();
    const code   = document.getElementById('edit-code').value.trim();
    const amount = document.getElementById('edit-amount').value;
    const errEl  = document.getElementById('edit-name-error');
    if (!name) { errEl.textContent = 'Name is required.'; errEl.classList.remove('hidden'); return; }
    errEl.classList.add('hidden');

    const btn = document.getElementById('edit-btn');
    btn.disabled = true; btn.textContent = 'Saving…';
    try {
        const old    = document.getElementById('item-row-' + editId);
        const oldCls = old?.dataset.cls;
        const res  = await fetch(`${UPDATE_BASE}/${editId}`, {
            method:'PUT',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({object_class:oldCls, object_code:code, name, appropriation:amount}),
        });
        const data = await res.json();
        if (!res.ok) { errEl.textContent = data.error||'Failed.'; errEl.classList.remove('hidden'); return; }

        if (old) old.replaceWith(buildRow(data.item));
        recalcAll();
        closeEdit();
        showToast(data.message);
    } catch(e) { showToast('Network error.','error'); }
    finally { btn.disabled=false; btn.textContent='Update'; }
}

// ── Delete ─────────────────────────────────────────────
function confirmDelete(id, name) {
    Swal.fire({
        title:'Delete line item?', text:`"${name}" will be removed.`,
        icon:'warning', showCancelButton:true,
        confirmButtonText:'Delete', cancelButtonText:'Cancel', confirmButtonColor:'#fa5c7c',
    }).then(async r => {
        if (!r.isConfirmed) return;
        try {
            const res  = await fetch(`${UPDATE_BASE}/${id}`, {method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}});
            const data = await res.json();
            if (!res.ok) { showToast(data.error||'Failed.','error'); return; }
            document.getElementById('item-row-'+id)?.remove();
            recalcAll();
            showToast(data.message);
        } catch(e) { showToast('Network error.','error'); }
    });
}

// ── Seed ───────────────────────────────────────────────
async function seedDefaults(progId, cls) {
    try {
        const res  = await fetch(SEED_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({fiscal_year_id:FY_ID, program_id:progId, object_class:cls}),
        });
        const data = await res.json();
        if (!res.ok) { showToast(data.error||'Failed.','error'); return; }
        const container = document.getElementById(`items-${progId}-${cls}`);
        data.items.forEach(item => container.appendChild(buildRow(item)));
        recalcAll();
        showToast(data.message);
    } catch(e) { showToast('Network error.','error'); }
}

// ── Collapse ───────────────────────────────────────────
function toggleCard(progId, headerEl) {
    const body    = document.getElementById('prog-body-' + progId);
    const chevron = document.getElementById('prog-chevron-' + progId);
    const hidden  = body.classList.toggle('hidden');
    chevron.style.transform = hidden ? 'rotate(-90deg)' : '';
}

function toggleCls(progId, cls, headerEl) {
    const body    = document.getElementById('items-' + progId + '-' + cls);
    const chevron = document.getElementById('cls-chevron-' + progId + '-' + cls);
    const hidden  = body.classList.toggle('hidden');
    chevron.style.transform = hidden ? 'rotate(-90deg)' : '';
}

// ── Toggle voucher sub-rows ────────────────────────────
function toggleVouchers(itemId, btn) {
    const el = document.getElementById('vouchers-' + itemId);
    if (!el) return;
    const isVisible = el.style.display !== 'none';
    el.style.display = isVisible ? 'none' : 'block';
    btn.querySelector('i').className = isVisible ? 'mgc_bill_line' : 'mgc_bill_2_line';
}

// ── Backdrop close ─────────────────────────────────────
['add-modal','edit-modal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) { this.classList.add('hidden'); this.classList.remove('flex'); }
    });
});
</script>
@endpush
