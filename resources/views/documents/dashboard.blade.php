@extends('layouts.vertical', [
    'title'         => 'Documents Dashboard',
    'sub_title'     => 'Documents',
    'sub_title_url' => route('documents.requests.index'),
    'tagline'       => 'Requests overview, rankings, and revenue.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

<style>
/* Equal-height chart rows (matches the demographics page) */
.chart-row { display:grid; gap:1.5rem; margin-bottom:1.5rem; }
.chart-row .card { display:flex; flex-direction:column; margin-bottom:0; }
.chart-row .card .p-5 { flex:1; }
</style>

{{-- ── Date range filter ── --}}
<form method="GET" action="{{ route('documents.dashboard') }}" class="mb-6 flex flex-wrap items-end gap-2">
    @php $ranges = ['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month']; @endphp
    <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        @foreach($ranges as $key => $label)
        <a href="{{ route('documents.dashboard', ['range' => $key]) }}"
           class="px-3 py-2 text-sm {{ $range === $key ? 'bg-primary text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
    {{-- Custom range --}}
    <input type="hidden" name="range" value="custom">
    <input type="date" name="from" value="{{ $range === 'custom' ? $from->format('Y-m-d') : '' }}"
           class="form-input text-sm py-1.5 w-40" placeholder="From">
    <input type="date" name="to" value="{{ $range === 'custom' ? $to->format('Y-m-d') : '' }}"
           class="form-input text-sm py-1.5 w-40" placeholder="To">
    <button type="submit" class="btn bg-primary text-white text-sm py-1.5 px-4">Apply</button>
    <span class="text-xs text-gray-400 ms-auto self-center">
        {{ $from->format('M d, Y') }} — {{ $to->format('M d, Y') }}
    </span>
</form>

{{-- ══════════════════════════════════════════════════════════════════════════
     KPI STAT CARDS — demographics style (icon-left, label + big number)
     ══════════════════════════════════════════════════════════════════════════ --}}
<p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">Overview</p>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="card">
        <div class="p-4 flex items-center gap-3 h-full">
            <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center"><i class="mgc_inbox_line text-lg text-primary"></i></div>
            <div class="min-w-0 flex-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total Requests</p>
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($kpis['total']) }}</h3>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="p-4 flex items-center gap-3 h-full">
            <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-success/10 flex items-center justify-center"><i class="mgc_check_circle_line text-lg text-success"></i></div>
            <div class="min-w-0 flex-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">Released</p>
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($kpis['released']) }}</h3>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="p-4 flex items-center gap-3 h-full">
            <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-info/10 flex items-center justify-center"><i class="mgc_print_line text-lg text-info"></i></div>
            <div class="min-w-0 flex-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total Prints</p>
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($kpis['prints']) }}</h3>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="p-4 flex items-center gap-3 h-full">
            <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-warning/10 flex items-center justify-center"><i class="mgc_wallet_3_line text-lg text-warning"></i></div>
            <div class="min-w-0 flex-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">Fees Collected</p>
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100">₱ {{ number_format($kpis['fees'], 2) }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- ── Row 1: Requests over time · Status pie · Fees over time ── --}}
<div class="chart-row lg:grid-cols-3">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title flex items-center gap-1"><i class="mgc_chart_line_line text-primary"></i> Requests Over Time</h4>
            <p class="text-xs text-gray-400 mt-0.5">Daily number of requests created in the selected period.</p>
        </div>
        <div class="p-5"><div id="chart-timeline" class="apex-charts" dir="ltr"></div></div>
    </div>
    <div class="card">
        <div class="card-header">
            <h4 class="card-title flex items-center gap-1"><i class="mgc_pie_2_line text-primary"></i> Status Breakdown</h4>
            <p class="text-xs text-gray-400 mt-0.5">Share of requests by current status.</p>
        </div>
        <div class="p-5"><div id="chart-status" class="apex-charts" dir="ltr"></div></div>
    </div>
    <div class="card">
        <div class="card-header">
            <h4 class="card-title flex items-center gap-1"><i class="mgc_wallet_3_line text-warning"></i> Fees Collected Over Time</h4>
            <p class="text-xs text-gray-400 mt-0.5">Paid fees (₱) per day, by release date.</p>
        </div>
        <div class="p-5"><div id="chart-revenue" class="apex-charts" dir="ltr"></div></div>
    </div>
</div>

{{-- ── Row 2: Top documents · Most printed · Highest-earning ── --}}
<div class="chart-row lg:grid-cols-3">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title flex items-center gap-1"><i class="mgc_document_2_line text-primary"></i> Top Documents Requested</h4>
            <p class="text-xs text-gray-400 mt-0.5">Most-requested document types.</p>
        </div>
        <div class="p-5">@if($topTypes->isEmpty())<p class="text-sm text-gray-400 py-8 text-center">No data.</p>@else<div id="chart-types" class="apex-charts" dir="ltr"></div>@endif</div>
    </div>
    <div class="card">
        <div class="card-header">
            <h4 class="card-title flex items-center gap-1"><i class="mgc_print_line text-info"></i> Most Printed Documents</h4>
            <p class="text-xs text-gray-400 mt-0.5">Documents with the most total prints.</p>
        </div>
        <div class="p-5">@if($topPrinted->isEmpty())<p class="text-sm text-gray-400 py-8 text-center">No prints yet.</p>@else<div id="chart-printed" class="apex-charts" dir="ltr"></div>@endif</div>
    </div>
    <div class="card">
        <div class="card-header">
            <h4 class="card-title flex items-center gap-1"><i class="mgc_wallet_3_line text-warning"></i> Highest-Earning Documents</h4>
            <p class="text-xs text-gray-400 mt-0.5">Documents that collected the most fees (₱).</p>
        </div>
        <div class="p-5">@if($topEarning->isEmpty())<p class="text-sm text-gray-400 py-8 text-center">No paid documents.</p>@else<div id="chart-earning" class="apex-charts" dir="ltr"></div>@endif</div>
    </div>
</div>

{{-- ── Row 3: Top purposes + Top 10 citizens ── --}}
<div class="chart-row lg:grid-cols-2">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title flex items-center gap-1"><i class="mgc_target_line text-primary"></i> Top Purposes</h4>
            <p class="text-xs text-gray-400 mt-0.5">Most common reasons for requesting documents.</p>
        </div>
        <div class="p-5">@if($topPurposes->isEmpty())<p class="text-sm text-gray-400 py-8 text-center">No data.</p>@else<div id="chart-purposes" class="apex-charts" dir="ltr"></div>@endif</div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title flex items-center gap-1"><i class="mgc_trophy_line text-warning"></i> Top 10 Citizens by Requests</h4>
            <p class="text-xs text-gray-400 mt-0.5">Citizens who requested the most documents in this period.</p>
        </div>
        <div class="p-5 overflow-y-auto" style="height: 320px;">
    @if($topCitizens->isEmpty())
        <p class="text-sm text-gray-400 py-6 text-center">No requests in this period.</p>
    @else
        @php $maxC = $topCitizens->max('value') ?: 1; @endphp
        <div class="space-y-3">
            @foreach($topCitizens as $i => $c)
            <div class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-primary/10 text-primary text-xs font-bold flex items-center justify-center shrink-0">{{ $i + 1 }}</span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <a href="{{ route('citizens.index') }}" class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate hover:text-primary">{{ $c['label'] }}</a>
                        <span class="text-xs font-semibold text-gray-500 shrink-0">{{ $c['value'] }} request{{ $c['value'] > 1 ? 's' : '' }}</span>
                    </div>
                    <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-primary rounded-full" style="width: {{ round($c['value'] / $maxC * 100) }}%"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
    </div>
    </div>
</div>

@endsection

@section('script')
<script src="/js/apexcharts.min.js"></script>
<script>
(function () {
    const dark = document.documentElement.getAttribute('data-mode') === 'dark';
    const labelColor = dark ? '#9ca3af' : '#6b7280';
    const gridColor  = dark ? '#374151' : '#e5e7eb';

    // ── Requests over time (line/area) ──
    new ApexCharts(document.getElementById('chart-timeline'), {
        chart: { type: 'area', height: 280, toolbar: { show: false } },
        series: [{ name: 'Requests', data: @json(array_column($timeline, 'count')) }],
        xaxis: { categories: @json(array_column($timeline, 'date')), labels: { style: { colors: labelColor } } },
        yaxis: { labels: { style: { colors: labelColor } } },
        colors: ['#727cf5'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
        grid: { borderColor: gridColor },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => v + ' request(s)' } },
    }).render();

    // ── Fees collected over time (bar) ──
    new ApexCharts(document.getElementById('chart-revenue'), {
        chart: { type: 'bar', height: 260, toolbar: { show: false } },
        series: [{ name: 'Collected', data: @json(array_column($revenueTimeline, 'amount')) }],
        xaxis: { categories: @json(array_column($revenueTimeline, 'date')), labels: { style: { colors: labelColor } } },
        yaxis: { labels: { style: { colors: labelColor }, formatter: v => '₱' + Number(v).toLocaleString() } },
        colors: ['#ffbc00'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
        grid: { borderColor: gridColor },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => '₱ ' + Number(v).toLocaleString(undefined, {minimumFractionDigits: 2}) } },
    }).render();

    // ── Status pie ── (colors match the status badges used in the requests list)
    const statusData = @json($statusChart);
    const statusColors = {
        'Pending':           '#eab308', // yellow-500
        'Approved':          '#0ea5e9', // sky-500
        'Ready for release': '#eab308', // yellow-500
        'Released':          '#22c55e', // green-500
        'Rejected':          '#ef4444', // red-500
    };
    if (statusData.length) {
        new ApexCharts(document.getElementById('chart-status'), {
            chart: { type: 'pie', height: 280, toolbar: { show: false } },
            series: statusData.map(s => s.value),
            labels: statusData.map(s => s.label),
            colors: statusData.map(s => statusColors[s.label] || '#98a6ad'),
            legend: { position: 'bottom', labels: { colors: labelColor } },
            dataLabels: { style: { colors: ['#fff'] } },
            tooltip: { y: { formatter: v => v + ' request(s)' } },
        }).render();
    }

    // Reusable horizontal bar for rankings.
    function rankBar(elId, data, color, isMoney, height) {
        const el = document.getElementById(elId);
        if (!el || !data.length) return;
        new ApexCharts(el, {
            chart: { type: 'bar', height: height || 300, toolbar: { show: false } },
            series: [{ name: isMoney ? 'Amount' : 'Count', data: data.map(d => d.value) }],
            xaxis: { categories: data.map(d => d.label), labels: { style: { colors: labelColor } } },
            yaxis: { labels: { style: { colors: labelColor }, maxWidth: 160 } },
            colors: [color],
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
            grid: { borderColor: gridColor },
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: v => isMoney ? ('₱ ' + Number(v).toLocaleString(undefined, {minimumFractionDigits: 2})) : (v + '') } },
        }).render();
    }

    rankBar('chart-types',     @json($topTypes),    '#727cf5', false);
    rankBar('chart-printed',   @json($topPrinted),  '#39afd1', false);
    rankBar('chart-earning',   @json($topEarning),  '#ffbc00', true);
    rankBar('chart-purposes',  @json($topPurposes), '#0acf97', false, 280);
})();
</script>
@endsection
