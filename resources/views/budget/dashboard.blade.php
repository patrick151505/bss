@extends('layouts.vertical', [
    'title'         => 'Budget Dashboard',
    'sub_title'     => 'Budget',
    'sub_title_url' => route('budget.index'),
    'tagline'       => $activeFy ? $activeFy->displayLabel() : 'No Active Fiscal Year',
])

@section('content')

{{-- Fiscal Year Selector + alerts --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-3 flex-wrap">
        <form method="GET" action="{{ route('budget.index') }}">
            <select name="fy" onchange="this.form.submit()" class="form-select form-select-sm w-44">
                @foreach($fiscalYears as $fy)
                    <option value="{{ $fy->id }}" {{ $activeFy?->id == $fy->id ? 'selected' : '' }}>
                        {{ $fy->displayLabel() }}{{ $fy->is_active ? ' ★' : '' }}
                    </option>
                @endforeach
            </select>
        </form>

        @if(isset($overdueCas) && $overdueCas->count())
            <span class="badge bg-danger/15 text-danger font-semibold px-3 py-1.5 text-sm">
                <i class="mgc_warning_line me-1"></i>
                {{ $overdueCas->count() }} overdue cash advance{{ $overdueCas->count() > 1 ? 's' : '' }}
            </span>
        @endif
    </div>

    <form method="POST" action="{{ route('budget.fiscal-years.store') }}" class="flex gap-1">
        @csrf
        <input type="number" name="year" placeholder="{{ date('Y') + 1 }}" min="2000" max="2100"
            class="form-input form-input-sm w-24" required>
        <input type="number" name="beginning_cash_balance" placeholder="Opening bal." step="0.01" min="0"
            class="form-input form-input-sm w-32">
        <button type="submit" class="btn btn-sm btn-primary">+ Add FY</button>
    </form>
</div>

@if(!$activeFy)
    <div class="alert alert-info">
        <i class="mgc_information_line me-2"></i>
        No fiscal year found. Add one above to get started.
    </div>
@else

{{-- Summary Cards --}}
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="card p-5 text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Total Appropriation</p>
        <p class="text-2xl font-bold text-primary">₱{{ number_format($totals['appropriation'], 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">Adjusted (incl. supplementals)</p>
    </div>
    <div class="card p-5 text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Disbursed</p>
        <p class="text-2xl font-bold text-danger">₱{{ number_format($totals['disbursed'], 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">Approved vouchers only</p>
    </div>
    <div class="card p-5 text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Remaining Balance</p>
        <p class="text-2xl font-bold {{ $totals['balance'] < 0 ? 'text-danger' : 'text-success' }}">
            ₱{{ number_format($totals['balance'], 2) }}
        </p>
        <p class="text-xs text-gray-400 mt-1">Appropriation − Disbursed</p>
    </div>
    <div class="card p-5 text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Tax Withheld</p>
        <p class="text-2xl font-bold text-warning">₱{{ number_format($totals['tax'], 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">Creditable withholding tax</p>
    </div>
</div>

{{-- Balance Ledger --}}
<div class="card mb-6">
    <div class="card-header flex items-center justify-between">
        <h5 class="card-title">Appropriation Balance Ledger — {{ $activeFy->displayLabel() }}</h5>
        <a href="{{ route('budget.allocations.index', ['fy' => $activeFy->id]) }}" class="btn btn-sm btn-light">
            <i class="mgc_settings_2_line me-1"></i> Manage Lines
        </a>
    </div>
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Object Class</th>
                        <th>Line Item</th>
                        <th class="text-end">Appropriation</th>
                        <th class="text-end">Disbursed</th>
                        <th class="text-end">Balance</th>
                        <th class="w-28">Usage</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($allocationRows as $row)
                        @php
                            $classBadge = match($row->alloc->object_class) {
                                'PS'      => 'bg-primary/15 text-primary',
                                'MOOE'    => 'bg-success/15 text-success',
                                'CO'      => 'bg-warning/15 text-warning',
                                'special' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                                default   => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <tr>
                            <td><span class="badge {{ $classBadge }} text-xs">{{ $row->alloc->object_class }}</span></td>
                            <td>
                                {{ $row->alloc->line_name }}
                                @if($row->alloc->fund_type !== 'general')
                                    <span class="text-xs text-gray-400 ms-1">({{ str_replace('_', ' ', $row->alloc->fund_type) }})</span>
                                @endif
                            </td>
                            <td class="text-end font-mono text-sm">₱{{ number_format($row->adjusted, 2) }}</td>
                            <td class="text-end font-mono text-sm text-danger">₱{{ number_format($row->disbursed, 2) }}</td>
                            <td class="text-end font-mono text-sm {{ $row->balance < 0 ? 'text-danger font-semibold' : 'text-success' }}">
                                ₱{{ number_format($row->balance, 2) }}
                            </td>
                            <td>
                                <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full mb-0.5">
                                    <div class="h-1.5 rounded-full {{ $row->pct >= 90 ? 'bg-danger' : ($row->pct >= 70 ? 'bg-warning' : 'bg-success') }}"
                                        style="width: {{ min(100, $row->pct) }}%"></div>
                                </div>
                                <span class="text-xs text-gray-400">{{ $row->pct }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-400 py-10">
                                No appropriation lines set up.
                                <a href="{{ route('budget.allocations.index', ['fy' => $activeFy->id]) }}" class="text-primary">Set up budget lines →</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($allocationRows->count())
                <tfoot class="font-semibold bg-gray-50 dark:bg-gray-800/60">
                    <tr>
                        <td colspan="2">Total</td>
                        <td class="text-end font-mono">₱{{ number_format($totals['appropriation'], 2) }}</td>
                        <td class="text-end font-mono text-danger">₱{{ number_format($totals['disbursed'], 2) }}</td>
                        <td class="text-end font-mono {{ $totals['balance'] < 0 ? 'text-danger' : 'text-success' }}">₱{{ number_format($totals['balance'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">

    {{-- Open Cash Advances --}}
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h5 class="card-title">Open Cash Advances</h5>
            <a href="{{ route('budget.cash-advances.create') }}" class="btn btn-sm btn-primary">+ Grant CA</a>
        </div>
        <div class="card-body p-0">
            @forelse($openCas as $ca)
                @php $overdue = $ca->isOverdue(); @endphp
                <div class="flex items-start justify-between px-4 py-3 border-b last:border-0 hover:bg-gray-50 dark:hover:bg-gray-800/40 {{ $overdue ? 'bg-danger/5' : '' }}">
                    <div class="min-w-0">
                        <p class="font-medium text-sm truncate">{{ $ca->officer->name }}</p>
                        <p class="text-xs text-gray-500">{{ $ca->ca_no }} — {{ Str::limit($ca->purpose, 40) }}</p>
                        <p class="text-xs {{ $overdue ? 'text-danger font-semibold' : 'text-gray-400' }} mt-0.5">
                            Due: {{ $ca->deadline_date->format('M d, Y') }}
                            @if($overdue) · <span>{{ $ca->daysOverdue() }}d overdue</span> @endif
                        </p>
                    </div>
                    <div class="text-end ms-4 shrink-0">
                        <p class="font-semibold text-sm">₱{{ number_format($ca->amount, 2) }}</p>
                        <a href="{{ route('budget.cash-advances.show', $ca) }}" class="text-xs text-primary hover:underline">View →</a>
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-400 py-8 text-sm">
                    <i class="mgc_check_circle_line text-2xl text-success mb-2 block"></i>
                    No open cash advances.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Recent Vouchers --}}
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h5 class="card-title">Recent Vouchers</h5>
            <a href="{{ route('budget.transactions.index', ['fy' => $activeFy->id]) }}" class="text-sm text-primary hover:underline">View all →</a>
        </div>
        <div class="card-body p-0">
            @forelse($recentTx as $tx)
                @php $s = \App\Models\BudgetTransaction::STATUSES[$tx->status] ?? null; @endphp
                <div class="flex items-start justify-between px-4 py-3 border-b last:border-0 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                    <div class="min-w-0">
                        <p class="font-medium text-sm truncate">{{ $tx->payee ?: '—' }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $tx->voucher_type }}{{ $tx->voucher_no ? ' #'.$tx->voucher_no : '' }}
                            @if($tx->allocation) · {{ Str::limit($tx->allocation->line_name, 30) }} @endif
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $tx->transaction_date->format('M d, Y') }}</p>
                    </div>
                    <div class="text-end ms-4 shrink-0">
                        <p class="font-semibold text-sm text-danger">₱{{ number_format($tx->amount, 2) }}</p>
                        @if($s) <span class="badge text-xs {{ $s['color'] }}">{{ $s['label'] }}</span> @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-400 py-8 text-sm">No vouchers recorded yet.</div>
            @endforelse
        </div>
    </div>

</div>

{{-- Monthly Chart --}}
<div class="card mb-6">
    <div class="card-header"><h5 class="card-title">Monthly Disbursements — {{ $activeFy->displayLabel() }}</h5></div>
    <div class="card-body"><div style="height:220px"><canvas id="monthlyChart"></canvas></div></div>
</div>

{{-- Fiscal Year Management --}}
<div class="card">
    <div class="card-header"><h5 class="card-title">Fiscal Years</h5></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead><tr><th>Year</th><th>Label</th><th class="text-end">Opening Balance</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @foreach($fiscalYears as $fy)
                <tr>
                    <td class="font-semibold">{{ $fy->year }}</td>
                    <td>{{ $fy->label ?: '—' }}</td>
                    <td class="text-end font-mono">₱{{ number_format($fy->beginning_cash_balance, 2) }}</td>
                    <td>
                        @if($fy->is_active)
                            <span class="badge bg-success/15 text-success">Active</span>
                        @else
                            <span class="badge bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if(!$fy->is_active)
                            <form method="POST" action="{{ route('budget.fiscal-years.activate', $fy) }}" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-xs btn-light">Set Active</button>
                            </form>
                            <form method="POST" action="{{ route('budget.fiscal-years.destroy', $fy) }}" class="inline"
                                onsubmit="return confirm('Delete FY {{ $fy->year }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-danger-light">Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function() {
    const el = document.getElementById('monthlyChart');
    if (!el) return;
    new Chart(el, {
        type: 'bar',
        data: {
            labels: @json($chartData['labels']),
            datasets: [{
                label: 'Disbursed (₱)',
                data: @json($chartData['disbursed']),
                backgroundColor: 'rgba(239,68,68,0.65)',
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { ticks: { callback: v => '₱' + Number(v).toLocaleString() } }
            }
        }
    });
})();
</script>
@endpush
