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
            <select name="fy" onchange="this.form.submit()" class="form-select text-sm py-1.5 w-44">
                @foreach($fiscalYears as $fy)
                    <option value="{{ $fy->id }}" {{ $activeFy?->id == $fy->id ? 'selected' : '' }}>
                        {{ $fy->displayLabel() }}{{ $fy->is_active ? ' ★' : '' }}
                    </option>
                @endforeach
            </select>
        </form>

        @if(isset($overdueCas) && $overdueCas->count())
            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-red-100 text-red-800">
                <i class="mgc_warning_line"></i>
                {{ $overdueCas->count() }} overdue cash advance{{ $overdueCas->count() > 1 ? 's' : '' }}
            </span>
        @endif
        @if(($pendingCount ?? 0) > 0)
            <a href="{{ route('budget.transactions.index', ['fy' => $activeFy?->id, 'status' => 'draft']) }}"
               class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 hover:bg-yellow-200 transition">
                <i class="mgc_alert_line"></i>
                {{ $pendingCount }} voucher{{ $pendingCount > 1 ? 's' : '' }} pending approval
            </a>
        @endif
    </div>

    @can('budget.create')
    <form method="POST" action="{{ route('budget.fiscal-years.store') }}" class="flex items-center gap-2">
        @csrf
        <input type="number" name="year" placeholder="{{ date('Y') + 1 }}" min="2000" max="2100"
            class="form-input text-sm py-1.5 w-24" required>
        <input type="number" name="beginning_cash_balance" placeholder="Opening bal." step="0.01" min="0"
            class="form-input text-sm py-1.5 w-32">
        <button type="submit" class="btn bg-primary text-white text-sm py-1.5 px-4 whitespace-nowrap">
            <i class="mgc_add_line me-1"></i> Add FY
        </button>
    </form>
    @endcan
</div>

@if(!$activeFy)
    <div class="p-4 rounded-lg bg-info/10 border border-info/30 flex gap-3">
        <i class="mgc_information_line text-info text-xl mt-0.5 shrink-0"></i>
        <p class="text-sm text-info font-medium">No fiscal year found. Add one above to get started.</p>
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

{{-- Utilization ring + Spending-by-class donut --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="card p-5 flex flex-col items-center justify-center">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Budget Utilization</p>
        <div style="width:170px;height:170px" class="relative">
            <div id="utilChart"></div>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-3xl font-bold {{ $totals['utilization'] >= 90 ? 'text-danger' : 'text-primary' }}">{{ $totals['utilization'] }}%</span>
                <span class="text-xs text-gray-400">disbursed</span>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-3 text-center">
            ₱{{ number_format($totals['disbursed'], 0) }} of ₱{{ number_format($totals['appropriation'], 0) }}
        </p>
    </div>

    <div class="card p-5 lg:col-span-2">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Spending by Object Class</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
            <div id="classChart" style="height:200px"></div>
            <div class="space-y-2">
                @php $classMeta = ['PS'=>['Personnel Services','text-primary'],'MOOE'=>['Operations (MOOE)','text-success'],'CO'=>['Capital Outlay','text-warning']]; @endphp
                @foreach($byClass as $class => $c)
                    @php [$label, $color] = $classMeta[$class] ?? [$class, 'text-gray-500']; @endphp
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full inline-block
                                {{ $class==='PS'?'bg-primary':($class==='MOOE'?'bg-success':'bg-warning') }}"></span>
                            <span class="text-gray-600 dark:text-gray-300">{{ $label }}</span>
                        </span>
                        <span class="font-mono font-medium {{ $color }}">₱{{ number_format($c->disbursed, 0) }}</span>
                    </div>
                @endforeach
                <div class="flex items-center justify-between text-sm border-t pt-2 mt-2 dark:border-gray-700">
                    <span class="text-gray-500">Total disbursed</span>
                    <span class="font-mono font-semibold">₱{{ number_format($totals['disbursed'], 0) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Budget per Program --}}
<div class="card mb-6">
    <div class="card-header"><h5 class="card-title">Budget per Program — {{ $activeFy->displayLabel() }}</h5></div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Program</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Appropriation</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Disbursed</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Balance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-48">Utilization</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($byProgram as $p)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                    <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $p->name }}</td>
                    <td class="px-6 py-3 text-right font-mono text-gray-700 dark:text-gray-300">₱{{ number_format($p->appropriation, 2) }}</td>
                    <td class="px-6 py-3 text-right font-mono text-danger">₱{{ number_format($p->disbursed, 2) }}</td>
                    <td class="px-6 py-3 text-right font-mono {{ $p->balance < 0 ? 'text-danger font-semibold' : 'text-success' }}">₱{{ number_format($p->balance, 2) }}</td>
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full">
                                <div class="h-1.5 rounded-full {{ $p->pct >= 90 ? 'bg-danger' : ($p->pct >= 70 ? 'bg-warning' : 'bg-success') }}"
                                     style="width: {{ $p->pct }}%"></div>
                            </div>
                            <span class="text-xs text-gray-400 w-8 text-right">{{ $p->pct }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-gray-400 py-10">No program allocations set up.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Balance Ledger --}}
<div class="card mb-6">
    <div class="card-header flex items-center justify-between">
        <h5 class="card-title">Appropriation Balance Ledger — {{ $activeFy->displayLabel() }}</h5>
        <a href="{{ route('budget.allocations.index', ['fy' => $activeFy->id]) }}"
           class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white inline-flex items-center gap-1">
            <i class="mgc_settings_2_line"></i> Manage Lines
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-20">Class</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Line Item</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Appropriation</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Disbursed</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Balance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-32">Usage</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($allocationRows as $row)
                    @php
                        $classBadge = match($row->alloc->object_class) {
                            'PS'   => 'bg-primary/10 text-primary',
                            'MOOE' => 'bg-green-100 text-green-800',
                            'CO'   => 'bg-yellow-100 text-yellow-800',
                            default=> 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center py-0.5 px-2 rounded-full text-xs font-semibold {{ $classBadge }}">{{ $row->alloc->object_class }}</span>
                        </td>
                        <td class="px-6 py-3 text-gray-800 dark:text-gray-200">
                            {{ $row->alloc->name ?? $row->alloc->program?->name ?? '—' }}
                            @if($row->alloc->program)
                                <span class="text-xs text-gray-400 ms-1">· {{ $row->alloc->program->name }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right font-mono text-gray-700 dark:text-gray-300">₱{{ number_format($row->adjusted, 2) }}</td>
                        <td class="px-6 py-3 text-right font-mono text-danger">₱{{ number_format($row->disbursed, 2) }}</td>
                        <td class="px-6 py-3 text-right font-mono {{ $row->balance < 0 ? 'text-danger font-semibold' : 'text-success' }}">₱{{ number_format($row->balance, 2) }}</td>
                        <td class="px-6 py-3">
                            <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full mb-0.5">
                                <div class="h-1.5 rounded-full {{ $row->pct >= 90 ? 'bg-danger' : ($row->pct >= 70 ? 'bg-warning' : 'bg-success') }}"
                                    style="width: {{ min(100, $row->pct) }}%"></div>
                            </div>
                            <span class="text-xs text-gray-400">{{ $row->pct }}%</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                            No appropriation lines set up.
                            <a href="{{ route('budget.allocations.index', ['fy' => $activeFy->id]) }}" class="text-primary">Set up budget lines →</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($allocationRows->count())
            <tfoot class="font-semibold bg-gray-50 dark:bg-gray-700/60 border-t-2 border-gray-200 dark:border-gray-600">
                <tr>
                    <td colspan="2" class="px-6 py-3 text-gray-800 dark:text-gray-100">Total</td>
                    <td class="px-6 py-3 text-right font-mono text-gray-800 dark:text-gray-100">₱{{ number_format($totals['appropriation'], 2) }}</td>
                    <td class="px-6 py-3 text-right font-mono text-danger">₱{{ number_format($totals['disbursed'], 2) }}</td>
                    <td class="px-6 py-3 text-right font-mono {{ $totals['balance'] < 0 ? 'text-danger' : 'text-success' }}">₱{{ number_format($totals['balance'], 2) }}</td>
                    <td class="px-6 py-3"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">

    {{-- Open Cash Advances --}}
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h5 class="card-title">Open Cash Advances</h5>
            @can('budget.create')
            <a href="{{ route('budget.cash-advances.create') }}" class="btn btn-sm bg-primary text-white inline-flex items-center gap-1"><i class="mgc_add_line"></i> Grant CA</a>
            @endcan
        </div>
        <div class="p-0">
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
                    <div class="text-right ms-4 shrink-0">
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
        <div class="p-0">
            @forelse($recentTx as $tx)
                @php $s = \App\Models\BudgetTransaction::STATUSES[$tx->status] ?? null; @endphp
                <div class="flex items-start justify-between px-4 py-3 border-b last:border-0 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                    <div class="min-w-0">
                        <p class="font-medium text-sm truncate">{{ $tx->payee ?: '—' }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $tx->voucher_type }}{{ $tx->voucher_no ? ' #'.$tx->voucher_no : '' }}
                            @php $firstItem = $tx->lines->first()?->lineItem; @endphp
                            @if($firstItem) · {{ Str::limit($firstItem->name, 28) }}@if($tx->lines->count() > 1) +{{ $tx->lines->count() - 1 }}@endif @endif
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $tx->transaction_date->format('M d, Y') }}</p>
                    </div>
                    <div class="text-right ms-4 shrink-0">
                        <p class="font-semibold text-sm text-danger">₱{{ number_format($tx->amount, 2) }}</p>
                        @if($s) <span class="inline-flex items-center py-0.5 px-2 rounded-full text-xs font-medium {{ $s['color'] }}">{{ $s['label'] }}</span> @endif
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
    <div class="p-6"><div id="monthlyChart" style="height:220px"></div></div>
</div>

{{-- Fiscal Year Management --}}
<div class="card">
    <div class="card-header"><h5 class="card-title">Fiscal Years</h5></div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Year</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Label</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Opening Balance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($fiscalYears as $fy)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                    <td class="px-6 py-3 font-semibold text-gray-800 dark:text-gray-100">{{ $fy->year }}</td>
                    <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $fy->label ?: '—' }}</td>
                    <td class="px-6 py-3 text-right font-mono text-gray-700 dark:text-gray-300">₱{{ number_format($fy->beginning_cash_balance, 2) }}</td>
                    <td class="px-6 py-3">
                        @if($fy->is_active)
                            <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-400 inline-block"></span>Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span>Inactive
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-right whitespace-nowrap">
                        @if(!$fy->is_active)
                            <div class="inline-flex items-center gap-1.5">
                                @can('budget.edit')
                                <form method="POST" action="{{ route('budget.fiscal-years.activate', $fy) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm bg-success/25 text-success hover:bg-success hover:text-white">Set Active</button>
                                </form>
                                @endcan
                                @can('budget.delete')
                                <form method="POST" action="{{ route('budget.fiscal-years.destroy', $fy) }}"
                                    onsubmit="return confirm('Delete FY {{ $fy->year }}? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm bg-danger/25 text-danger hover:bg-danger hover:text-white">Delete</button>
                                </form>
                                @endcan
                            </div>
                        @else
                            <span class="text-xs text-gray-400">—</span>
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

@section('script')
{{-- ApexCharts, served locally (no internet needed) — same as the demographics page --}}
<script src="/js/apexcharts.min.js"></script>
<script>
(function () {
    if (typeof ApexCharts === 'undefined') return;
    const peso = v => '₱' + Number(v).toLocaleString();

    // ── Monthly disbursements (bar) ───────────────────────────────────────
    const monthlyEl = document.getElementById('monthlyChart');
    if (monthlyEl) {
        new ApexCharts(monthlyEl, {
            chart: { type: 'bar', height: 220, toolbar: { show: false }, fontFamily: 'inherit' },
            series: [{ name: 'Disbursed', data: @json($chartData['disbursed']) }],
            xaxis: { categories: @json($chartData['labels']), labels: { style: { colors: '#94a3b8' } } },
            yaxis: { labels: { formatter: peso, style: { colors: '#94a3b8' } } },
            colors: ['#fa5c7c'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
            dataLabels: { enabled: false },
            grid: { borderColor: 'rgba(148,163,184,0.15)' },
            tooltip: { y: { formatter: peso } },
        }).render();
    }

    // ── Budget utilization (radial gauge) ────────────────────────────────
    const utilEl = document.getElementById('utilChart');
    if (utilEl) {
        const pct = {{ $totals['utilization'] }};
        new ApexCharts(utilEl, {
            chart: { type: 'radialBar', height: 170, sparkline: { enabled: true }, fontFamily: 'inherit' },
            series: [pct],
            colors: [pct >= 90 ? '#fa5c7c' : '#727cf5'],
            plotOptions: {
                radialBar: {
                    hollow: { size: '72%' },
                    track: { background: 'rgba(148,163,184,0.18)' },
                    dataLabels: { show: false },
                },
            },
            stroke: { lineCap: 'round' },
        }).render();
    }

    // ── Spending by object class (donut) ─────────────────────────────────
    const clsEl   = document.getElementById('classChart');
    const clsData = @json($chartData['byClass']['data'] ?? []);
    const clsLabels = @json($chartData['byClass']['labels'] ?? []);
    if (clsEl) {
        if (clsData.some(v => v > 0)) {
            new ApexCharts(clsEl, {
                chart: { type: 'donut', height: 200, toolbar: { show: false }, fontFamily: 'inherit' },
                series: clsData,
                labels: clsLabels,
                colors: ['#727cf5', '#0acf97', '#ffbc00'],
                legend: { show: false },
                dataLabels: { enabled: false },
                plotOptions: { pie: { donut: { size: '62%' } } },
                stroke: { width: 0 },
                tooltip: { y: { formatter: peso } },
            }).render();
        } else {
            clsEl.innerHTML = '<div class="h-full flex items-center justify-center text-gray-400 text-sm" style="height:200px">No spending yet</div>';
        }
    }
})();
</script>
@endsection
