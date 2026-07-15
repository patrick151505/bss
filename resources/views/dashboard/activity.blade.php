@extends('layouts.vertical', [
    'title'     => 'Activity Dashboard',
    'sub_title' => 'Dashboard',
    'tagline'   => 'Real-time overview of barangay operations and staff activity.',
    'mode'      => $mode ?? '',
    'demo'      => $demo ?? '',
])

@section('content')

{{-- ── ROW 1: KPI Cards ──────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- Citizens --}}
    <div class="card p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center">
                <i class="mgc_group_line text-primary text-xl"></i>
            </div>
            <span class="text-xs font-semibold px-2 py-1 rounded-full bg-green-100 text-green-700">
                +{{ $kpi['citizens_month'] }} mo
            </span>
        </div>
        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($kpi['total_citizens']) }}</p>
        <p class="text-xs text-gray-500 mt-1">Total Citizens</p>
        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center gap-1 text-xs text-gray-400">
            <i class="mgc_add_circle_line text-green-500"></i>
            <span><strong class="text-gray-700 dark:text-gray-300">{{ $kpi['citizens_today'] }}</strong> registered today</span>
        </div>
    </div>

    {{-- Households --}}
    <div class="card p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-11 h-11 rounded-xl bg-info/10 flex items-center justify-center">
                <i class="mgc_home_3_line text-info text-xl"></i>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($kpi['total_households']) }}</p>
        <p class="text-xs text-gray-500 mt-1">Households</p>
        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center gap-1 text-xs text-gray-400">
            <i class="mgc_document_2_line text-info"></i>
            <span><strong class="text-gray-700 dark:text-gray-300">{{ $kpi['docs_pending'] }}</strong> document requests pending</span>
        </div>
    </div>

    {{-- Blotter --}}
    <div class="card p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-11 h-11 rounded-xl bg-warning/10 flex items-center justify-center">
                <i class="mgc_alert_line text-warning text-xl"></i>
            </div>
            <span class="text-xs font-semibold px-2 py-1 rounded-full bg-yellow-100 text-yellow-700">
                {{ $kpi['blotters_week'] }} this wk
            </span>
        </div>
        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $kpi['blotters_open'] }}</p>
        <p class="text-xs text-gray-500 mt-1">Open Blotter Cases</p>
        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center gap-1 text-xs text-gray-400">
            <i class="mgc_calendar_line text-warning"></i>
            <span><strong class="text-gray-700 dark:text-gray-300">{{ $kpi['events_upcoming'] }}</strong> upcoming events</span>
        </div>
    </div>

    {{-- Budget --}}
    <div class="card p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-11 h-11 rounded-xl bg-success/10 flex items-center justify-center">
                <i class="mgc_wallet_3_line text-success text-xl"></i>
            </div>
            @if($kpi['budget_pending'] > 0)
            <span class="text-xs font-semibold px-2 py-1 rounded-full bg-orange-100 text-orange-700">
                {{ $kpi['budget_pending'] }} pending
            </span>
            @endif
        </div>
        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">₱{{ number_format($kpi['budget_paid'], 0) }}</p>
        <p class="text-xs text-gray-500 mt-1">Total Disbursed (Paid)</p>
        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center gap-1 text-xs text-gray-400">
            <i class="mgc_check_circle_line text-success"></i>
            <span>Across all vouchers this year</span>
        </div>
    </div>

</div>

{{-- ── 2-COLUMN: Feed (left) | Hourly + Module stacked (right) ─────── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6" style="grid-template-rows: auto auto;">

    {{-- LEFT spans 2 rows: Live Activity Feed --}}
    <div class="card flex flex-col" style="grid-row: 1 / 3;">
        <div class="card-header">
            <div class="flex items-center justify-between">
                <h4 class="card-title flex items-center gap-2">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse inline-block"></span>
                    Live Activity Feed
                </h4>
                <a href="{{ route('activity-logs.index') }}" class="text-xs text-primary hover:underline">View all</a>
            </div>
        </div>
        <div class="p-4 space-y-1 flex-1" id="activityFeed"></div>
        <div class="px-4 pb-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700 pt-3">
            <span class="text-xs text-gray-400" id="feedInfo"></span>
            <div class="flex items-center gap-1">
                <button id="feedPrev" class="btn btn-sm bg-dark/10 text-gray-600 dark:text-gray-300 hover:bg-dark/20 disabled:opacity-40 disabled:cursor-not-allowed px-3 py-1.5 text-xs">
                    <i class="mgc_arrow_left_line"></i> Prev
                </button>
                <button id="feedNext" class="btn btn-sm bg-dark/10 text-gray-600 dark:text-gray-300 hover:bg-dark/20 disabled:opacity-40 disabled:cursor-not-allowed px-3 py-1.5 text-xs">
                    Next <i class="mgc_arrow_right_line"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- RIGHT row 1: Today's Hourly Activity --}}
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Today's Activity</h4>
            <p class="text-xs text-gray-400 mt-0.5">Actions by hour — {{ now()->format('M d, Y') }}</p>
        </div>
        <div class="p-4">
            <div id="hourlyChart" class="h-[240px]"></div>
        </div>
    </div>

    {{-- RIGHT row 2: Module Breakdown --}}
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Module Breakdown</h4>
            <p class="text-xs text-gray-400 mt-0.5">Actions by module (last 30 days)</p>
        </div>
        <div class="p-4">
            <div id="moduleChart" class="h-[150px]"></div>
            <div class="mt-3 space-y-2">
                @php
                    $moduleColors = ['#3b82f6','#22c55e','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#6b7280'];
                @endphp
                @foreach($moduleBreakdown->take(6) as $i => $m)
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $moduleColors[$i % count($moduleColors)] }}"></span>
                        <span class="text-gray-600 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $m->subject_type) }}</span>
                    </div>
                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $m->total }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

{{-- ── Weekly Chart full width below ───────────────────────────────── --}}
<div class="card mb-6">
    <div class="card-header">
        <h4 class="card-title">Weekly Activity (Last 7 Days)</h4>
        <p class="text-xs text-gray-400 mt-0.5">Total logged actions per day</p>
    </div>
    <div class="p-4">
        <div id="weeklyChart" class="h-[220px]"></div>
    </div>
</div>

{{-- ── Budget Voucher Charts ────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- Bar: Top vouchers by amount --}}
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Top Vouchers by Amount</h4>
            <p class="text-xs text-gray-400 mt-0.5">Highest disbursements across all statuses</p>
        </div>
        <div class="p-4">
            <div id="voucherBarChart" class="h-[280px]"></div>
        </div>
    </div>

    {{-- Donut: Total disbursed by status --}}
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Vouchers by Status</h4>
            <p class="text-xs text-gray-400 mt-0.5">Total amount grouped by payment status</p>
        </div>
        <div class="p-4 flex flex-col items-center">
            <div id="voucherDonutChart" class="h-[200px] w-full"></div>
            <div class="mt-4 w-full space-y-2">
                @php
                    $statusColorMap = [
                        'paid'      => '#0acf97',
                        'approved'  => '#727cf5',
                        'draft'     => '#ffbc00',
                        'cancelled' => '#fa5c7c',
                    ];
                @endphp
                @foreach($voucherDonut ?? [] as $row)
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $statusColorMap[$row->status] ?? '#6b7280' }}"></span>
                        <span class="text-gray-600 dark:text-gray-400 capitalize">{{ $row->status }}</span>
                    </div>
                    <span class="font-semibold text-gray-700 dark:text-gray-300">₱{{ number_format($row->total, 0) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

{{-- ── ROW 4: Recent Tables ────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    {{-- Recent Document Requests --}}
    <div class="card">
        <div class="card-header">
            <div class="flex items-center justify-between">
                <h4 class="card-title">Document Requests</h4>
                <a href="{{ route('documents.requests.index') }}" class="text-xs text-primary hover:underline">View all</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="table min-w-full text-sm">
                <tbody>
                    @forelse($recentDocs as $doc)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800 dark:text-gray-200 text-xs leading-tight">
                                {{ $doc->citizen?->fname }} {{ $doc->citizen?->lname }}
                            </p>
                            <p class="text-[11px] text-gray-400">{{ $doc->documentType?->name }}</p>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @php
                                $dc = match($doc->status) {
                                    'released' => 'bg-green-100 text-green-700',
                                    'approved' => 'bg-blue-100 text-blue-700',
                                    'rejected' => 'bg-red-100 text-red-700',
                                    default    => 'bg-yellow-100 text-yellow-700',
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1 py-0.5 px-2 rounded-full text-[10px] font-semibold {{ $dc }}">
                                {{ ucfirst($doc->status) }}
                            </span>
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $doc->created_at->format('M d') }}</p>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="text-center py-8 text-gray-400 text-xs">No requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Blotter Cases --}}
    <div class="card">
        <div class="card-header">
            <div class="flex items-center justify-between">
                <h4 class="card-title">Blotter Cases</h4>
                <a href="{{ route('blotters.index') }}" class="text-xs text-primary hover:underline">View all</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="table min-w-full text-sm">
                <tbody>
                    @forelse($recentBlotters as $blotter)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-800 dark:text-gray-200 text-xs">{{ $blotter->blotter_no }}</p>
                            <p class="text-[11px] text-gray-400">{{ \App\Models\Blotter::TYPES[$blotter->type] ?? $blotter->type }}</p>
                            <p class="text-[11px] text-gray-500 truncate max-w-[150px]">{{ $blotter->complainant_name }}</p>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @php
                                $bc = match($blotter->status) {
                                    'settled'  => 'bg-green-100 text-green-700',
                                    'dismissed'=> 'bg-gray-100 text-gray-600',
                                    'referred' => 'bg-blue-100 text-blue-700',
                                    'ongoing'  => 'bg-yellow-100 text-yellow-700',
                                    'closed'   => 'bg-gray-100 text-gray-600',
                                    default    => 'bg-red-100 text-red-700',
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1 py-0.5 px-2 rounded-full text-[10px] font-semibold {{ $bc }}">
                                {{ ucfirst($blotter->status) }}
                            </span>
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $blotter->filed_date->format('M d') }}</p>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="text-center py-8 text-gray-400 text-xs">No blotter cases yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Budget Vouchers --}}
    <div class="card">
        <div class="card-header">
            <div class="flex items-center justify-between">
                <h4 class="card-title">Budget Vouchers</h4>
                <a href="{{ route('budget.transactions.index') }}" class="text-xs text-primary hover:underline">View all</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="table min-w-full text-sm">
                <tbody>
                    @forelse($recentVouchers as $v)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-800 dark:text-gray-200 text-xs">{{ $v->voucher_no }}</p>
                            <p class="text-[11px] text-gray-400 truncate max-w-[150px]">{{ $v->payee }}</p>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @php
                                $vc = match($v->status) {
                                    'paid'      => 'bg-green-100 text-green-700',
                                    'approved'  => 'bg-blue-100 text-blue-700',
                                    'cancelled' => 'bg-red-100 text-red-700',
                                    default     => 'bg-yellow-100 text-yellow-700',
                                };
                            @endphp
                            <p class="text-xs font-bold text-gray-700 dark:text-gray-300">₱{{ number_format($v->amount, 0) }}</p>
                            <span class="inline-flex items-center py-0.5 px-2 rounded-full text-[10px] font-semibold {{ $vc }}">
                                {{ ucfirst($v->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="text-center py-8 text-gray-400 text-xs">No vouchers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- ── ROW 4: Staff Activity ───────────────────────────────────────── --}}
<div class="card mb-6">
    <div class="card-header">
        <h4 class="card-title">Staff Activity</h4>
        <p class="text-xs text-gray-400 mt-0.5">Actions performed by each staff member</p>
    </div>
    <div class="overflow-x-auto">
        <table class="table min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Staff Member</th>
                    <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Today</th>
                    <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">This Week</th>
                    <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">All Time</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Last Active</th>
                    <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staffActivity as $staff)
                <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold shrink-0">
                                {{ strtoupper(substr($staff->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 dark:text-gray-200 text-sm">{{ $staff->name }}</p>
                                <p class="text-xs text-gray-400">{{ $staff->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ $staff->today_actions ?? 0 }}</span>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $staff->week_actions ?? 0 }}</span>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="text-gray-600 dark:text-gray-400">{{ $staff->total_actions ?? 0 }}</span>
                    </td>
                    <td class="px-5 py-3 text-xs text-gray-500">
                        {{ $staff->last_active ? \Carbon\Carbon::parse($staff->last_active)->diffForHumans() : '—' }}
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium {{ $staff->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            <span class="w-1.5 h-1.5 rounded-full inline-block {{ $staff->is_active ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                            {{ $staff->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-10 text-gray-400">
                        <i class="mgc_user_3_line text-3xl mb-2 block opacity-30"></i>No staff found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('inline-scripts')
<script src="/js/apexcharts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Activity Feed Pagination ──────────────────────────────────
    const ACTION_CONFIG = {
        created:        ['bg-green-100 text-green-600',   'mgc_add_circle_line'],
        updated:        ['bg-blue-100 text-blue-600',     'mgc_edit_line'],
        deleted:        ['bg-red-100 text-red-600',       'mgc_delete_line'],
        exported:       ['bg-orange-100 text-orange-600', 'mgc_download_2_line'],
        tags_updated:   ['bg-purple-100 text-purple-600', 'mgc_tag_2_line'],
        status_updated: ['bg-yellow-100 text-yellow-600', 'mgc_refresh_1_line'],
        action_added:   ['bg-teal-100 text-teal-600',     'mgc_add_line'],
        action_deleted: ['bg-red-100 text-red-600',       'mgc_close_line'],
    };
    const DEFAULT_CFG = ['bg-gray-100 text-gray-500', 'mgc_history_line'];

    const feedItems = @json($feedJson);

    const PER_PAGE  = 10;
    let currentPage = 1;
    const totalPages = Math.ceil(feedItems.length / PER_PAGE);

    const feedEl  = document.getElementById('activityFeed');
    const infoEl  = document.getElementById('feedInfo');
    const prevBtn = document.getElementById('feedPrev');
    const nextBtn = document.getElementById('feedNext');

    function renderFeed(page) {
        const start = (page - 1) * PER_PAGE;
        const slice = feedItems.slice(start, start + PER_PAGE);

        if (!slice.length) {
            feedEl.innerHTML = `<div class="text-center py-12 text-gray-400">
                <i class="mgc_history_line text-4xl mb-2 block opacity-30"></i>No activity logged yet.</div>`;
            infoEl.textContent = '';
            prevBtn.disabled = nextBtn.disabled = true;
            return;
        }

        feedEl.innerHTML = slice.map(item => {
            const [cls, icon] = ACTION_CONFIG[item.action] ?? DEFAULT_CFG;
            const badge = item.subject_type
                ? `<span class="text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500">${item.subject_type.replace(/_/g,' ')}</span>`
                : '';
            return `<div class="flex items-start gap-3 py-2.5 border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                <div class="w-8 h-8 rounded-full ${cls} flex items-center justify-center shrink-0 mt-0.5">
                    <i class="${icon} text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-snug">
                        <span class="font-semibold">${item.user}</span> ${item.description}
                    </p>
                    <div class="flex items-center gap-2 mt-0.5">
                        ${badge}
                        <span class="text-[11px] text-gray-400">${item.time}</span>
                    </div>
                </div>
            </div>`;
        }).join('');

        const from = start + 1;
        const to   = Math.min(start + PER_PAGE, feedItems.length);
        infoEl.textContent = `Showing ${from}–${to} of ${feedItems.length}`;
        prevBtn.disabled = page <= 1;
        nextBtn.disabled = page >= totalPages;
    }

    prevBtn.addEventListener('click', () => { if (currentPage > 1) renderFeed(--currentPage); });
    nextBtn.addEventListener('click', () => { if (currentPage < totalPages) renderFeed(++currentPage); });

    renderFeed(currentPage);

    // ── Weekly Bar Chart ──────────────────────────────────────────
    new ApexCharts(document.getElementById('weeklyChart'), {
        chart: { type: 'bar', height: 260, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [{ name: 'Actions', data: {!! json_encode($weeklyData) !!} }],
        xaxis: { categories: {!! json_encode($weeklyLabels) !!}, labels: { style: { fontSize: '11px' } } },
        yaxis: { labels: { style: { fontSize: '11px' } }, min: 0, forceNiceScale: true },
        colors: ['#3b82f6'],
        plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
        dataLabels: { enabled: false },
        grid: { borderColor: '#f0f0f0', strokeDashArray: 3 },
        tooltip: { theme: 'light' },
    }).render();

    // ── Hourly Area Chart ─────────────────────────────────────────
    new ApexCharts(document.getElementById('hourlyChart'), {
        chart: { type: 'area', height: 240, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [{ name: 'Actions', data: {!! json_encode($hourlyValues) !!} }],
        xaxis: { categories: {!! json_encode($hourlyLabels) !!}, tickAmount: 6, labels: { style: { fontSize: '10px' }, rotate: -30 } },
        yaxis: { labels: { style: { fontSize: '10px' } }, min: 0, forceNiceScale: true },
        colors: ['#22c55e'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
        stroke: { curve: 'smooth', width: 2 },
        dataLabels: { enabled: false },
        grid: { borderColor: '#f0f0f0', strokeDashArray: 3 },
        tooltip: { theme: 'light' },
    }).render();

    // ── Module Donut Chart ────────────────────────────────────────
    @if($moduleBreakdown->count())
    new ApexCharts(document.getElementById('moduleChart'), {
        chart: { type: 'donut', height: 200, fontFamily: 'Inter, sans-serif' },
        series: {!! json_encode($moduleBreakdown->pluck('total')) !!},
        labels: {!! json_encode($moduleBreakdown->pluck('subject_type')->map(function($s) { return ucfirst($s ?? 'unknown'); })) !!},
        colors: {!! json_encode(array_slice(['#3b82f6','#22c55e','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#6b7280'], 0, $moduleBreakdown->count())) !!},
        legend: { show: false },
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '70%' } } },
        tooltip: { theme: 'light' },
    }).render();
    @else
    document.getElementById('moduleChart').innerHTML = '<div class="flex items-center justify-center h-full text-gray-400 text-sm">No data yet</div>';
    @endif

    // ── Voucher Bar Chart ─────────────────────────────────────────
    new ApexCharts(document.getElementById('voucherBarChart'), {
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [{ name: 'Amount (₱)', data: {!! json_encode($voucherBarAmounts) !!} }],
        xaxis: {
            categories: {!! json_encode($voucherBarLabels) !!},
            labels: { style: { fontSize: '10px' }, trim: true, maxHeight: 60 },
        },
        yaxis: {
            labels: {
                style: { fontSize: '10px' },
                formatter: val => '₱' + Number(val).toLocaleString(),
            },
        },
        colors: {!! json_encode($voucherBarColors) !!},
        plotOptions: { bar: { borderRadius: 5, columnWidth: '55%', distributed: true } },
        legend: { show: false },
        dataLabels: { enabled: false },
        grid: { borderColor: '#f0f0f0', strokeDashArray: 3 },
        tooltip: {
            y: { formatter: val => '₱' + Number(val).toLocaleString() },
        },
    }).render();

    // ── Voucher Donut Chart ───────────────────────────────────────
    new ApexCharts(document.getElementById('voucherDonutChart'), {
        chart: { type: 'donut', height: 200, fontFamily: 'Inter, sans-serif' },
        series: {!! json_encode($voucherDonutAmounts) !!},
        labels: {!! json_encode($voucherDonutLabels) !!},
        colors: {!! json_encode($voucherDonutColors) !!},
        legend: { show: false },
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '65%', labels: {
            show: true,
            total: {
                show: true,
                label: 'Total',
                formatter: w => '₱' + Number(w.globals.seriesTotals.reduce((a, b) => a + b, 0)).toLocaleString(),
            },
        } } } },
        tooltip: { y: { formatter: val => '₱' + Number(val).toLocaleString() } },
    }).render();

    // ── Auto-refresh every 60s ────────────────────────────────────
    setTimeout(() => location.reload(), 60000);
});
</script>
@endpush
