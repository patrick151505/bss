@extends('layouts.vertical', [
    'title'         => 'Budget Entry',
    'sub_title'     => 'Budget',
    'sub_title_url' => route('budget.index'),
    'tagline'       => 'Programmed Appropriation by PPA — fill in the Proposed Amount column',
])

@php
    $classes = \App\Models\BudgetLineItem::OBJECT_CLASSES;
    $badges  = [
        'PS'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        'MOOE' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        'FE'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        'CO'   => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
    ];

    // Totals count only funded rows; a blank amount means "listed, not funded".
    $totals = ['PS' => 0.0, 'MOOE' => 0.0, 'FE' => 0.0, 'CO' => 0.0];
    foreach ($itemsByProgram as $items) {
        foreach ($items as $item) {
            if ($item->appropriation !== null) {
                $totals[$item->object_class] += (float) $item->appropriation;
            }
        }
    }
    $grand = array_sum($totals);
@endphp

@section('content')

{{-- Toast --}}
<div id="toast" class="fixed top-5 right-5 z-[9999] hidden">
    <div id="toast-inner" class="flex items-center gap-2 px-4 py-2.5 rounded-lg shadow-lg text-xs font-medium min-w-[220px]"></div>
</div>

{{-- Fiscal year + actions --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div class="flex items-center gap-3">
        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Fiscal Year:</label>
        <form method="GET" action="{{ route('budget.entry.index') }}">
            <select name="fy" class="form-select w-36 text-sm" onchange="this.form.submit()">
                @foreach($fiscalYears as $fyOpt)
                    <option value="{{ $fyOpt->id }}" {{ $fyOpt->id == $fy?->id ? 'selected' : '' }}>
                        {{ $fyOpt->displayLabel() }}{{ $fyOpt->is_active ? ' ★' : '' }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @if($fy)
    <div class="flex items-center gap-2">
        <button type="button" onclick="applyTemplate()" class="btn btn-sm bg-dark/25 text-slate-900 hover:bg-dark hover:text-white dark:text-slate-200">
            <i class="mgc_download_2_line"></i> Load standard accounts
        </button>
        <button type="button" id="save-btn" onclick="saveAmounts()" class="btn bg-primary text-white" disabled>
            <i class="mgc_check_line"></i> Save amounts
        </button>
    </div>
    @endif
</div>

@if(!$fy)
    <p class="text-sm text-gray-400">No fiscal year found. <a href="{{ route('budget.index') }}" class="text-primary underline">Create one →</a></p>
@else

{{-- Class subtotals --}}
<div class="flex flex-wrap items-center mb-5 bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl overflow-hidden divide-x dark:divide-gray-700">
    @foreach($classes as $cls => $label)
    <div class="flex items-center gap-2 px-4 py-3 flex-1 min-w-[160px]">
        <span class="text-xs font-semibold px-1.5 py-0.5 rounded {{ $badges[$cls] }}">{{ $cls }}</span>
        <div>
            <div class="text-xs text-gray-400">{{ \Illuminate\Support\Str::before($label, ' (') }}</div>
            <div class="tabular-nums font-semibold text-sm text-gray-700 dark:text-gray-200" data-total="{{ $cls }}">₱{{ number_format($totals[$cls], 2) }}</div>
        </div>
    </div>
    @endforeach
    <div class="flex items-center gap-2 px-4 py-3 flex-1 min-w-[160px] bg-primary/5">
        <div>
            <div class="text-xs text-gray-400 uppercase tracking-wide">Total Appropriation</div>
            <div class="tabular-nums font-bold text-base text-primary" data-total="GRAND">₱{{ number_format($grand, 2) }}</div>
        </div>
    </div>
</div>

@if($itemsByProgram->isEmpty())
    <div class="card p-12 text-center">
        <i class="mgc_box_3_line text-4xl mb-2 block opacity-30 text-gray-400"></i>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            No accounts listed for {{ $fy->displayLabel() }} yet.
        </p>
        <button type="button" onclick="applyTemplate()" class="btn bg-primary text-white mx-auto">
            <i class="mgc_download_2_line"></i> Load standard accounts
        </button>
    </div>
@else

@foreach($programs as $program)
    @php
        $nodes = $program->children->isNotEmpty() ? $program->children : collect([$program]);
        $hasAny = $nodes->contains(fn ($n) => ($itemsByProgram[$n->id] ?? collect())->isNotEmpty());
    @endphp
    @continue(!$hasAny)

    <div class="card mb-5">
        <div class="card-header">
            <div class="flex justify-between items-center">
                <h4 class="card-title">
                    <span class="text-gray-400 mr-1">{{ $program->section_label }}.</span>{{ $program->name }}
                </h4>
                <span class="tabular-nums text-sm font-semibold text-gray-700 dark:text-gray-200"
                      data-program-total="{{ $program->id }}">₱0.00</span>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-6">
        @foreach($nodes as $node)
            @php $nodeItems = $itemsByProgram[$node->id] ?? collect(); @endphp
            @continue($nodeItems->isEmpty())

            @if($node->id !== $program->id)
            <div class="flex items-center justify-between border-b dark:border-gray-700 pb-2">
                <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    <span class="text-gray-400 mr-1">{{ $node->section_label }}.</span>{{ $node->name }}
                    @if($node->special_fund)
                        <span class="ml-2 inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-[10px] font-medium bg-primary/25 text-sky-800 dark:text-sky-300">
                            <span class="w-1.5 h-1.5 inline-block bg-sky-400 rounded-full"></span>
                            {{ \App\Models\BudgetProgram::SPECIAL_FUNDS[$node->special_fund] }}
                        </span>
                    @endif
                </h5>
            </div>
            @endif

            @foreach($nodeItems->groupBy('object_class') as $cls => $clsItems)
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-semibold px-1.5 py-0.5 rounded {{ $badges[$cls] ?? '' }}">{{ $cls }}</span>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $classes[$cls] ?? $cls }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="table min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-28">AIP Ref.</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Account</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-32">Account Code</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-48">Proposed Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $lastGroup = null; @endphp
                            @foreach($clsItems as $item)
                                <tr class="border-t dark:border-gray-700">
                                    <td class="px-4 py-2 font-mono text-xs text-gray-400">{{ $item->aip_code }}</td>
                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-200">
                                        {{ $item->name }}
                                        @if($item->expected_result)
                                            <span class="block text-xs text-gray-400 mt-0.5">{{ \Illuminate\Support\Str::limit($item->expected_result, 90) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 font-mono text-xs text-gray-400">{{ $item->object_code ?? '—' }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <input type="text" inputmode="decimal"
                                               class="form-input text-sm text-right tabular-nums w-40 ml-auto amount-input"
                                               data-id="{{ $item->id }}"
                                               data-class="{{ $item->object_class }}"
                                               data-program="{{ $program->id }}"
                                               data-original="{{ $item->appropriation !== null ? number_format((float) $item->appropriation, 2, '.', '') : '' }}"
                                               value="{{ $item->appropriation !== null ? number_format((float) $item->appropriation, 2, '.', '') : '' }}"
                                               placeholder="—">
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="border-t dark:border-gray-700 bg-gray-50/60 dark:bg-gray-700/30">
                                <td colspan="3" class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase dark:text-gray-400">
                                    Total {{ $cls }}
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums font-semibold text-gray-800 dark:text-gray-100"
                                    data-subtotal="{{ $node->id }}-{{ $cls }}">₱0.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        @endforeach
        </div>
    </div>
@endforeach

@endif
@endif

@endsection

@push('inline-scripts')
<script>
const SAVE_URL  = '{{ route('budget.line-items.save-amounts') }}';
const SEED_URL  = '{{ route('budget.line-items.seed-defaults') }}';
const FY_ID     = {{ $fy?->id ?? 'null' }};
const CSRF      = '{{ csrf_token() }}';

let toastTimer;
function showToast(msg, type = 'success') {
    const el  = document.getElementById('toast');
    const inn = document.getElementById('toast-inner');
    inn.className = 'flex items-center gap-2 px-4 py-2.5 rounded-lg shadow-lg text-xs font-medium min-w-[220px] ' +
        (type === 'success' ? 'bg-success/10 border border-success/30 text-success' : 'bg-danger/10 border border-danger/30 text-danger');
    inn.innerHTML = `<i class="mgc_${type === 'success' ? 'check_circle' : 'alert'}_line text-base shrink-0"></i><span>${msg}</span>`;
    el.classList.remove('hidden');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.add('hidden'), 3000);
}

const peso = n => '₱' + parseFloat(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/** Blank means "listed but not funded" and contributes nothing to any total. */
function amountOf(input) {
    const raw = input.value.replace(/[^0-9.]/g, '');
    return raw === '' ? null : (parseFloat(raw) || 0);
}

function recalc() {
    const byClass = { PS: 0, MOOE: 0, FE: 0, CO: 0 };
    const bySub = {}, byProgram = {};

    document.querySelectorAll('.amount-input').forEach(input => {
        const amt = amountOf(input) || 0;
        const cls = input.dataset.class;
        const sub = input.closest('table').querySelector('[data-subtotal]')?.dataset.subtotal;

        byClass[cls] = (byClass[cls] || 0) + amt;
        if (sub) bySub[sub] = (bySub[sub] || 0) + amt;
        byProgram[input.dataset.program] = (byProgram[input.dataset.program] || 0) + amt;
    });

    Object.entries(bySub).forEach(([k, v]) => {
        const el = document.querySelector(`[data-subtotal="${k}"]`);
        if (el) el.textContent = peso(v);
    });
    Object.entries(byProgram).forEach(([k, v]) => {
        const el = document.querySelector(`[data-program-total="${k}"]`);
        if (el) el.textContent = peso(v);
    });
    Object.entries(byClass).forEach(([k, v]) => {
        const el = document.querySelector(`[data-total="${k}"]`);
        if (el) el.textContent = peso(v);
    });
    const grand = document.querySelector('[data-total="GRAND"]');
    if (grand) grand.textContent = peso(Object.values(byClass).reduce((a, b) => a + b, 0));
}

function dirtyInputs() {
    return [...document.querySelectorAll('.amount-input')].filter(i => {
        const cur = amountOf(i);
        const orig = i.dataset.original === '' ? null : parseFloat(i.dataset.original);
        return cur !== orig;
    });
}

function refreshSaveState() {
    const btn = document.getElementById('save-btn');
    if (!btn) return;
    const n = dirtyInputs().length;
    btn.disabled = n === 0;
    btn.innerHTML = n === 0
        ? '<i class="mgc_check_line"></i> Save amounts'
        : `<i class="mgc_check_line"></i> Save ${n} change${n === 1 ? '' : 's'}`;
}

document.addEventListener('input', e => {
    if (!e.target.classList.contains('amount-input')) return;
    recalc();
    refreshSaveState();
});

// Normalise to 2dp on blur, but keep a cleared box blank rather than 0.00.
document.addEventListener('blur', e => {
    if (!e.target.classList.contains('amount-input')) return;
    const amt = amountOf(e.target);
    e.target.value = amt === null ? '' : amt.toFixed(2);
    recalc();
    refreshSaveState();
}, true);

async function saveAmounts() {
    const dirty = dirtyInputs();
    if (!dirty.length) return;

    const btn = document.getElementById('save-btn');
    btn.disabled = true;

    try {
        const res = await fetch(SAVE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ amounts: dirty.map(i => ({ id: +i.dataset.id, amount: amountOf(i) })) }),
        });
        const data = await res.json();

        if (!res.ok) {
            showToast(data.message || 'Could not save amounts.', 'error');
            refreshSaveState();
            return;
        }

        dirty.forEach(i => {
            const amt = amountOf(i);
            i.dataset.original = amt === null ? '' : amt.toFixed(2);
        });
        showToast(data.message);
    } catch (err) {
        showToast('Network error — amounts were not saved.', 'error');
    }
    refreshSaveState();
}

async function applyTemplate() {
    Swal.fire({
        title: 'Load standard accounts?',
        text: 'Adds the standard accounts to this fiscal year with blank amounts. Amounts you have already entered are kept.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, load them',
        reverseButtons: true,
        didOpen: () => {
            document.querySelector('.swal2-confirm').style.setProperty('background-color', '#727cf5', 'important');
        },
    }).then(async r => {
        if (!r.isConfirmed) return;
        try {
            const res = await fetch(SEED_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ fiscal_year_id: FY_ID }),
            });
            const data = await res.json();
            showToast(data.message, res.ok ? 'success' : 'error');
            if (res.ok && (data.items || []).length) setTimeout(() => location.reload(), 900);
        } catch (err) {
            showToast('Network error — nothing was loaded.', 'error');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => { recalc(); refreshSaveState(); });
</script>
@endpush
