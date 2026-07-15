@extends('layouts.vertical', [
    'title'         => 'Income Estimates',
    'sub_title'     => 'Budget',
    'sub_title_url' => route('budget.index'),
    'tagline'       => 'Step 2 — Estimate income sources for the fiscal year',
])

@section('content')

@php
    use App\Models\IncomeEstimate;
    $sourceTypes = IncomeEstimate::SOURCE_TYPES;
    $bases       = \App\Models\BudgetSetting::BASES;
    $fyYear      = $fy->year;
@endphp

{{-- Fiscal Year selector --}}
<div class="flex items-center gap-3 mb-5">
    <label class="font-semibold text-sm">Fiscal Year:</label>
    <form method="GET" action="{{ route('budget.income-estimates.index') }}">
        <select name="fy" class="form-select w-40" onchange="this.form.submit()">
            @foreach($fiscalYears as $fyOption)
                <option value="{{ $fyOption->id }}" {{ $fyOption->id == $fy->id ? 'selected' : '' }}>
                    {{ $fyOption->displayLabel() }}
                </option>
            @endforeach
        </select>
    </form>
</div>

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-danger font-medium">{{ session('error') }}</p>
</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- Left: Income Table --}}
    <div class="xl:col-span-2 space-y-5">

        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h5 class="card-title">Income Sources — {{ $fy->displayLabel() }}</h5>
                <button class="btn btn-sm bg-primary text-white" onclick="document.getElementById('add-form').classList.toggle('hidden')">
                    <i class="mgc_add_line me-1"></i> Add Source
                </button>
            </div>

            {{-- Add form --}}
            <div id="add-form" class="{{ $errors->any() ? '' : 'hidden' }} border-b border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800">
                <form method="POST" action="{{ route('budget.income-estimates.store') }}">
                    @csrf
                    <input type="hidden" name="fiscal_year_id" value="{{ $fy->id }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="form-label">Type</label>
                            <select name="source_type" class="form-select" required>
                                @foreach($sourceTypes as $k => $label)
                                    <option value="{{ $k }}" {{ old('source_type') == $k ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Label / Description</label>
                            <input type="text" name="source_label" class="form-input" placeholder="e.g. Barangay Clearance Fees" required value="{{ old('source_label') }}">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="form-label">{{ $fyYear - 2 }} Actual (₱)</label>
                            <input type="number" name="two_years_ago_actual" class="form-input" step="0.01" min="0" value="{{ old('two_years_ago_actual') }}" placeholder="0.00">
                        </div>
                        <div>
                            <label class="form-label">{{ $fyYear - 1 }} Actual (₱)</label>
                            <input type="number" name="prior_year_actual" class="form-input" step="0.01" min="0" value="{{ old('prior_year_actual') }}" placeholder="0.00">
                        </div>
                        <div>
                            <label class="form-label">{{ $fyYear }} Estimated (₱) <span class="text-danger">*</span></label>
                            <input type="number" name="estimated_amount" class="form-input" step="0.01" min="0" required value="{{ old('estimated_amount', 0) }}">
                        </div>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button type="submit" class="btn btn-sm bg-primary text-white">Save Source</button>
                        <button type="button" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white" onclick="document.getElementById('add-form').classList.add('hidden')">Cancel</button>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body p-0">
                @if($estimates->isEmpty())
                    <div class="text-center py-12 text-gray-400">
                        <i class="mgc_wallet_3_line text-4xl mb-2 block opacity-30"></i>
                        No income sources yet. Click <strong>+ Add Source</strong> to begin.
                    </div>
                @else
                <div class="overflow-x-auto">
                <table class="table min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-36">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Description</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-36">{{ $fyYear - 2 }} Actual</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-36">{{ $fyYear - 1 }} Actual</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-40">{{ $fyYear }} Estimated</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-28">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($estimates as $est)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                                <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium
                                    {{ $est->source_type === 'nta' ? 'bg-purple-100 text-purple-800' :
                                      ($est->source_type === 'rpt' ? 'bg-blue-100 text-blue-800' :
                                      ($est->source_type === 'clearance' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')) }}">
                                    {{ $sourceTypes[$est->source_type] ?? $est->source_type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-800 dark:text-gray-200">{{ $est->source_label }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-500 dark:text-gray-400">
                                {{ $est->two_years_ago_actual !== null ? '₱' . number_format($est->two_years_ago_actual, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-500 dark:text-gray-400">
                                {{ $est->prior_year_actual !== null ? '₱' . number_format($est->prior_year_actual, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums font-semibold text-gray-800 dark:text-gray-200">
                                ₱{{ number_format($est->estimated_amount, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white"
                                    onclick="openEditModal({{ $est->id }}, '{{ $est->source_type }}', @js($est->source_label), {{ $est->estimated_amount }}, {{ $est->prior_year_actual ?? 'null' }}, {{ $est->two_years_ago_actual ?? 'null' }})">
                                    <i class="mgc_edit_line"></i>
                                </button>
                                <form id="del-est-{{ $est->id }}" method="POST" action="{{ route('budget.income-estimates.destroy', $est) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-sm bg-danger/25 text-danger hover:bg-danger hover:text-white"
                                        onclick="swalRemoveEstimate({{ $est->id }}, @js($est->source_label))">
                                        <i class="mgc_delete_line"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 dark:bg-gray-700 border-t-2 border-gray-300 dark:border-gray-600">
                            <td colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">
                                Total Estimated Income
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-base font-bold text-primary">
                                ₱{{ number_format($pools['total_budget'], 2) }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                </div>
                @endif
            </div>
        </div>

        {{-- Income pool summary --}}
        <div class="card">
            <div class="card-header"><h5 class="card-title">Computed Income Pools</h5></div>
            <div class="card-body">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4">
                    @foreach([
                        'nta'             => ['label'=>'NTA',                  'color'=>'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300'],
                        'general_fund'    => ['label'=>'General Fund',         'color'=>'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'],
                        'regular_sources' => ['label'=>'Est. Regular Sources', 'color'=>'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'],
                        'total_budget'    => ['label'=>'Total Annual Budget',  'color'=>'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300'],
                    ] as $key => $cfg)
                    <div class="rounded-lg p-3 {{ $cfg['color'] }}">
                        <p class="text-xs font-medium mb-1">{{ $cfg['label'] }}</p>
                        <p class="text-lg font-bold">₱{{ number_format($pools[$key], 2) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Mandatory funds --}}
    <div class="space-y-4">
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h5 class="card-title">Mandatory Fund Allocations</h5>
                <a href="{{ route('budget.settings.index') }}" class="text-xs text-primary hover:underline">Configure rates →</a>
            </div>
            <div class="card-body space-y-3 p-4">
                @php
                    $fundLabels = [
                        'dev_fund' => ['label'=>'Development Fund',  'icon'=>'mgc_building_4_line',   'color'=>'text-purple-600'],
                        'sk_fund'  => ['label'=>'SK Fund',           'icon'=>'mgc_group_line',         'color'=>'text-blue-600'],
                        'calamity' => ['label'=>'Calamity / LDRRM', 'icon'=>'mgc_alert_diamond_line', 'color'=>'text-red-600'],
                        'gad'      => ['label'=>'GAD Fund',          'icon'=>'mgc_female_line',        'color'=>'text-pink-600'],
                    ];
                @endphp

                @foreach($fundLabels as $fund => $cfg)
                @php
                    $rate   = $settings->{"{$fund}_rate"};
                    $base   = $settings->{"{$fund}_base"};
                    $amount = $mandatory[$fund];
                @endphp
                <div class="flex items-start justify-between border-b border-gray-100 dark:border-gray-700 pb-3 last:border-0 last:pb-0">
                    <div>
                        <div class="flex items-center gap-1.5 mb-0.5">
                            <i class="{{ $cfg['icon'] }} {{ $cfg['color'] }} text-base"></i>
                            <span class="font-semibold text-sm">{{ $cfg['label'] }}</span>
                        </div>
                        <p class="text-xs text-gray-400">
                            {{ $rate }}% of {{ $bases[$base] ?? $base }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-sm {{ $amount > 0 ? $cfg['color'] : 'text-gray-400' }}">
                            ₱{{ number_format($amount, 2) }}
                        </p>
                    </div>
                </div>
                @endforeach

                <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                    @php
                        $totalMandatory = array_sum($mandatory);
                        $discretionary  = $pools['total_budget'] - $totalMandatory;
                    @endphp
                    <div class="flex justify-between text-sm font-semibold">
                        <span>Total Mandatory</span>
                        <span class="text-danger">₱{{ number_format($totalMandatory, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-semibold mt-1">
                        <span>Discretionary Remainder</span>
                        <span class="{{ $discretionary >= 0 ? 'text-success' : 'text-danger' }}">
                            ₱{{ number_format($discretionary, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <p class="text-xs text-gray-400 leading-relaxed">
                <strong>Note:</strong> Mandatory fund amounts are auto-computed from your income estimates. After confirming these estimates, proceed to <a href="{{ route('budget.allocations.index') }}" class="text-primary hover:underline">Budget Matrix</a> to fill in the appropriation grid.
            </p>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-lg mx-4">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Edit Income Source</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="mgc_close_line text-xl"></i>
            </button>
        </div>
        <form method="POST" id="edit-form" action="">
            @csrf @method('PUT')
            <div class="p-4 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-gray-800 text-sm font-medium inline-block mb-2">Type</label>
                        <select name="source_type" id="edit_source_type" class="form-select" required>
                            @foreach($sourceTypes as $k => $lbl)
                                <option value="{{ $k }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-gray-800 text-sm font-medium inline-block mb-2">Label / Description</label>
                        <input type="text" name="source_label" id="edit_source_label" class="form-input w-full" required>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="text-gray-800 text-sm font-medium inline-block mb-2">{{ $fyYear - 2 }} Actual (₱)</label>
                        <input type="number" name="two_years_ago_actual" id="edit_two_years_ago" class="form-input w-full" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div>
                        <label class="text-gray-800 text-sm font-medium inline-block mb-2">{{ $fyYear - 1 }} Actual (₱)</label>
                        <input type="number" name="prior_year_actual" id="edit_prior_year_actual" class="form-input w-full" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div>
                        <label class="text-gray-800 text-sm font-medium inline-block mb-2">{{ $fyYear }} Estimated (₱)</label>
                        <input type="number" name="estimated_amount" id="edit_estimated_amount" class="form-input w-full" step="0.01" min="0" required>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 py-3 px-4 border-t dark:border-gray-700">
                <button type="button" onclick="closeEditModal()" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</button>
                <button type="submit" class="btn bg-primary text-white">Update Source</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('inline-scripts')
<script>
function swalRemoveEstimate(id, label) {
    Swal.fire({
        title: 'Remove Income Source?',
        text: `"${label}" will be removed.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#fa5c7c',
    }).then(result => {
        if (result.isConfirmed) document.getElementById('del-est-' + id).submit();
    });
}
function openEditModal(id, type, label, amount, prior, twoYearsAgo) {
    const base = '{{ url("budget/income-estimates") }}';
    document.getElementById('edit-form').action = `${base}/${id}`;
    document.getElementById('edit_source_type').value = type;
    document.getElementById('edit_source_label').value = label;
    document.getElementById('edit_estimated_amount').value = amount;
    document.getElementById('edit_prior_year_actual').value = prior ?? '';
    document.getElementById('edit_two_years_ago').value = twoYearsAgo ?? '';
    const modal = document.getElementById('edit-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeEditModal() {
    const modal = document.getElementById('edit-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>
@endpush
