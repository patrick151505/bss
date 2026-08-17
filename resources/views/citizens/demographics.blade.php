@extends('layouts.vertical', [
    'title'         => 'Demographics',
    'sub_title'     => 'Citizens',
    'sub_title_url' => route('citizens.index'),
    'tagline'       => 'Population breakdown and voter statistics',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

{{-- ── Tooltip helper ───────────────────────────────────────────────────────
     Usage: <span class="info-tip" data-tip="Your text here"></span>
──────────────────────────────────────────────────────────────────────────── --}}
<style>
.info-tip { position:relative; display:inline-flex; align-items:center; cursor:default; }
.info-tip::after {
    content: attr(data-tip);
    position: absolute;
    bottom: calc(100% + 6px);
    left: 50%;
    transform: translateX(-50%);
    background: #1e293b;
    color: #f1f5f9;
    font-size: 0.72rem;
    line-height: 1.4;
    padding: 6px 10px;
    border-radius: 6px;
    white-space: nowrap;
    max-width: 240px;
    white-space: normal;
    width: max-content;
    max-width: 220px;
    pointer-events: none;
    opacity: 0;
    transition: opacity .15s;
    z-index: 50;
    box-shadow: 0 4px 12px rgba(0,0,0,.25);
}
.info-tip::before {
    content:'';
    position:absolute;
    bottom: calc(100% + 1px);
    left:50%; transform:translateX(-50%);
    border:5px solid transparent;
    border-top-color:#1e293b;
    pointer-events:none;
    opacity:0;
    transition:opacity .15s;
    z-index:50;
}
.info-tip:hover::after,
.info-tip:hover::before { opacity:1; }

/* Equal-height chart rows */
.chart-row { display:grid; gap:1.5rem; margin-bottom:1.5rem; }
.chart-row .card { display:flex; flex-direction:column; }
.chart-row .card .p-5 { flex:1; }

/* Equal-height stat mini-cards */
.stat-grid { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
.stat-grid .card { height:100%; }
.stat-grid .card > div { height:100%; }
</style>

{{-- ══════════════════════════════════════════════════════════════════════════
     STAT CARDS — 3 columns, each a 2×2 mini-grid
     ══════════════════════════════════════════════════════════════════════════ --}}
<div class="grid lg:grid-cols-3 gap-6 mb-8">

    {{-- Column 1: Population --}}
    <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3 flex items-center gap-1">
            Population
            <span class="info-tip" data-tip="Active citizens only (is_active = 1). Male = gender 1, Female = gender 0.">
                <i class="mgc_information_line text-sm text-gray-400"></i>
            </span>
        </p>
        <div class="stat-grid">

            <div class="card anim-card">
                <div class="p-4 flex items-center gap-3 h-full">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                        <i class="mgc_group_line text-lg text-primary"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            Total
                            <span class="info-tip" data-tip="Total number of active registered citizens in the barangay.">
                                <i class="mgc_information_line text-xs text-gray-300"></i>
                            </span>
                        </p>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100" data-countup="{{ $stats['total'] }}">{{ number_format($stats['total']) }}</h3>
                        <p class="text-xs text-gray-400">active citizens</p>
                    </div>
                </div>
            </div>

            <div class="card anim-card">
                <div class="p-4 flex items-center gap-3 h-full">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-success/10 flex items-center justify-center">
                        <i class="mgc_check_circle_line text-lg text-success"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            Voters
                            <span class="info-tip" data-tip="Citizens marked as registered voters (voters = 1).">
                                <i class="mgc_information_line text-xs text-gray-300"></i>
                            </span>
                        </p>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100" data-countup="{{ $stats['voters'] }}">{{ number_format($stats['voters']) }}</h3>
                        <p class="text-xs text-gray-400">{{ $stats['total'] ? round($stats['voters'] / $stats['total'] * 100, 1) : 0 }}% of total</p>
                    </div>
                </div>
            </div>

            <div class="card anim-card">
                <div class="p-4 flex items-center gap-3 h-full">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <i class="mgc_male_line text-lg text-blue-500"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            Male
                            <span class="info-tip" data-tip="Citizens with gender = Male.">
                                <i class="mgc_information_line text-xs text-gray-300"></i>
                            </span>
                        </p>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100" data-countup="{{ $stats['male'] }}">{{ number_format($stats['male']) }}</h3>
                        <p class="text-xs text-gray-400">{{ $stats['total'] ? round($stats['male'] / $stats['total'] * 100, 1) : 0 }}% of total</p>
                    </div>
                </div>
            </div>

            <div class="card anim-card">
                <div class="p-4 flex items-center gap-3 h-full">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center">
                        <i class="mgc_female_line text-lg text-pink-500"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            Female
                            <span class="info-tip" data-tip="Citizens with gender = Female.">
                                <i class="mgc_information_line text-xs text-gray-300"></i>
                            </span>
                        </p>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100" data-countup="{{ $stats['female'] }}">{{ number_format($stats['female']) }}</h3>
                        <p class="text-xs text-gray-400">{{ $stats['total'] ? round($stats['female'] / $stats['total'] * 100, 1) : 0 }}% of total</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Column 2: Household & Family --}}
    <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3 flex items-center gap-1">
            Household &amp; Family
            <span class="info-tip" data-tip="Household = physical dwelling unit. Family = nuclear family group within a household. A household may contain multiple families.">
                <i class="mgc_information_line text-sm text-gray-400"></i>
            </span>
        </p>
        <div class="stat-grid">

            <div class="card anim-card">
                <div class="p-4 flex items-center gap-3 h-full">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                        <i class="mgc_home_3_line text-lg text-primary"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            Households
                            <span class="info-tip" data-tip="Total registered household units in the barangay.">
                                <i class="mgc_information_line text-xs text-gray-300"></i>
                            </span>
                        </p>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100" data-countup="{{ $hh['total_households'] }}">{{ number_format($hh['total_households']) }}</h3>
                        <p class="text-xs text-gray-400">registered units</p>
                    </div>
                </div>
            </div>

            <div class="card anim-card">
                <div class="p-4 flex items-center gap-3 h-full">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                        <i class="mgc_home_6_line text-lg text-indigo-500"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            Families
                            <span class="info-tip" data-tip="Total family groups across all households.">
                                <i class="mgc_information_line text-xs text-gray-300"></i>
                            </span>
                        </p>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100" data-countup="{{ $hh['total_families'] }}">{{ number_format($hh['total_families']) }}</h3>
                        <p class="text-xs text-gray-400">family groups</p>
                    </div>
                </div>
            </div>

            <div class="card anim-card">
                <div class="p-4 flex items-center gap-3 h-full">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                        <i class="mgc_user_3_line text-lg text-amber-500"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            Avg. Size
                            <span class="info-tip" data-tip="Average number of assigned citizens per household unit.">
                                <i class="mgc_information_line text-xs text-gray-300"></i>
                            </span>
                        </p>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100" data-countup="{{ $hh['avg_household_size'] }}" data-decimals="1">{{ $hh['avg_household_size'] }}</h3>
                        <p class="text-xs text-gray-400">members / household</p>
                    </div>
                </div>
            </div>

            <div class="card anim-card">
                <div class="p-4 flex items-center gap-3 h-full">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-danger/10 flex items-center justify-center">
                        <i class="mgc_user_remove_line text-lg text-danger"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            Unassigned
                            <span class="info-tip" data-tip="Active citizens with no household or family assignment (familyId is null).">
                                <i class="mgc_information_line text-xs text-gray-300"></i>
                            </span>
                        </p>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100" data-countup="{{ $hh['unassigned'] }}">{{ number_format($hh['unassigned']) }}</h3>
                        <p class="text-xs text-gray-400">no household</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Column 3: Special Sectors --}}
    <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3 flex items-center gap-1">
            Special Sectors
            <span class="info-tip" data-tip="DILG-reportable vulnerable sectors. PWD per RA 7277, Solo Parents per RA 8972, Seniors per RA 9994.">
                <i class="mgc_information_line text-sm text-gray-400"></i>
            </span>
        </p>
        <div class="stat-grid">

            <div class="card anim-card">
                <div class="p-4 flex items-center gap-3 h-full">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                        <i class="mgc_crutch_line text-lg text-purple-500"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            PWD
                            <span class="info-tip" data-tip="Persons with Disability (is_pwd = 1). Per RA 7277 — Magna Carta for Disabled Persons.">
                                <i class="mgc_information_line text-xs text-gray-300"></i>
                            </span>
                        </p>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100" data-countup="{{ $sectors['pwd'] }}">{{ number_format($sectors['pwd']) }}</h3>
                        <p class="text-xs text-gray-400">{{ $stats['total'] ? round($sectors['pwd'] / $stats['total'] * 100, 1) : 0 }}% of population</p>
                    </div>
                </div>
            </div>

            <div class="card anim-card">
                <div class="p-4 flex items-center gap-3 h-full">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                        <i class="mgc_baby_carriage_line text-lg text-orange-500"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            Solo Parents
                            <span class="info-tip" data-tip="Solo parents (is_soloparents = 1). Per RA 8972 — Solo Parents' Welfare Act.">
                                <i class="mgc_information_line text-xs text-gray-300"></i>
                            </span>
                        </p>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100" data-countup="{{ $sectors['solo_parents'] }}">{{ number_format($sectors['solo_parents']) }}</h3>
                        <p class="text-xs text-gray-400">{{ $stats['total'] ? round($sectors['solo_parents'] / $stats['total'] * 100, 1) : 0 }}% of population</p>
                    </div>
                </div>
            </div>

            <div class="card col-span-2">
                <div class="p-4 flex items-center gap-3 h-full">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                        <i class="mgc_sun_line text-lg text-teal-500"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            Senior Citizens (60+)
                            <span class="info-tip" data-tip="Citizens aged 60 and above. Per RA 9994 — Expanded Senior Citizens Act of 2010.">
                                <i class="mgc_information_line text-xs text-gray-300"></i>
                            </span>
                        </p>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100" data-countup="{{ $sectors['senior'] }}">{{ number_format($sectors['senior']) }}</h3>
                        <p class="text-xs text-gray-400">{{ $stats['total'] ? round($sectors['senior'] / $stats['total'] * 100, 1) : 0 }}% of population</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     CHARTS — 3-column grid, fixed height 320px per chart
     ══════════════════════════════════════════════════════════════════════════ --}}

{{-- Row 1: Gender | Age | Voters vs Non-Voters --}}
<div class="chart-row lg:grid-cols-3">
    <div class="card anim-card">
        <div class="card-header">
            <h4 class="card-title flex items-center gap-1">
                Gender
                <span class="info-tip" data-tip="Male vs Female proportion among all active citizens.">
                    <i class="mgc_information_line text-sm text-gray-400"></i>
                </span>
            </h4>
        </div>
        <div class="p-5"><div id="chart-gender" class="apex-charts" dir="ltr"></div></div>
    </div>
    <div class="card anim-card">
        <div class="card-header">
            <h4 class="card-title flex items-center gap-1">
                Age Distribution
                <span class="info-tip" data-tip="Citizens grouped by age: Children (0–12), Youth (13–17), Adult (18–59), Senior (60+).">
                    <i class="mgc_information_line text-sm text-gray-400"></i>
                </span>
            </h4>
        </div>
        <div class="p-5"><div id="chart-age" class="apex-charts" dir="ltr"></div></div>
    </div>
    <div class="card anim-card">
        <div class="card-header">
            <h4 class="card-title flex items-center gap-1">
                Voters vs Non-Voters
                <span class="info-tip" data-tip="Proportion of citizens who are registered voters vs those who are not.">
                    <i class="mgc_information_line text-sm text-gray-400"></i>
                </span>
            </h4>
        </div>
        <div class="p-5"><div id="chart-voters" class="apex-charts" dir="ltr"></div></div>
    </div>
</div>

{{-- Row 2: Civil Status | Voters by Age | Voters by Zone --}}
<div class="chart-row lg:grid-cols-3">
    <div class="card anim-card">
        <div class="card-header">
            <h4 class="card-title flex items-center gap-1">
                Civil Status
                <span class="info-tip" data-tip="Breakdown of citizens by marital status (Single, Married, Widowed, Separated, etc.).">
                    <i class="mgc_information_line text-sm text-gray-400"></i>
                </span>
            </h4>
        </div>
        <div class="p-5"><div id="chart-civil" class="apex-charts" dir="ltr"></div></div>
    </div>
    <div class="card anim-card">
        <div class="card-header">
            <h4 class="card-title flex items-center gap-1">
                Voters by Age Group
                <span class="info-tip" data-tip="Voter registration rate per age group. Children excluded as they are not eligible to vote.">
                    <i class="mgc_information_line text-sm text-gray-400"></i>
                </span>
            </h4>
        </div>
        <div class="p-5"><div id="chart-voters-age" class="apex-charts" dir="ltr"></div></div>
    </div>
    <div class="card anim-card">
        <div class="card-header">
            <h4 class="card-title flex items-center gap-1">
                Voters by Zone / Purok
                <span class="info-tip" data-tip="Number of registered voters per barangay zone or purok.">
                    <i class="mgc_information_line text-sm text-gray-400"></i>
                </span>
            </h4>
        </div>
        <div class="p-5"><div id="chart-voters-zone" class="apex-charts" dir="ltr"></div></div>
    </div>
</div>

{{-- Row 3: Members by Zone | Special Sectors by Zone (spans 2) --}}
<div class="chart-row lg:grid-cols-3">
    <div class="card anim-card">
        <div class="card-header">
            <h4 class="card-title flex items-center gap-1">
                Members per Zone
                <span class="info-tip" data-tip="Number of household-assigned citizens per barangay zone or purok.">
                    <i class="mgc_information_line text-sm text-gray-400"></i>
                </span>
            </h4>
        </div>
        <div class="p-5"><div id="chart-zone-members" class="apex-charts" dir="ltr"></div></div>
    </div>
    <div class="card lg:col-span-2">
        <div class="card-header">
            <h4 class="card-title flex items-center gap-1">
                Special Sectors by Zone / Purok
                <span class="info-tip" data-tip="Count of PWD, Solo Parents, and Senior Citizens broken down per zone. Useful for DILG sector reporting.">
                    <i class="mgc_information_line text-sm text-gray-400"></i>
                </span>
            </h4>
        </div>
        <div class="p-5"><div id="chart-sectors-zone" class="apex-charts" dir="ltr"></div></div>
    </div>
</div>

@include('partials.stat-animations')

@endsection

@section('script')
<script src="/js/apexcharts.min.js"></script>
<script>
(function () {
    var gender      = @json($charts['gender']);
    var age         = @json($charts['age']);
    var civil       = @json($charts['civil']);
    var voterSplit  = @json($charts['voter_split']);
    var voterAge    = @json($charts['voter_age']);
    var voterZone   = @json($charts['voter_zone']);
    var zoneMembers = @json($charts['zone_members']);
    var sectorsZone = @json($charts['sectors_zone']);

    var isDark     = document.documentElement.classList.contains('dark');
    var labelColor = isDark ? '#94a3b8' : '#64748b';
    var gridColor  = isDark ? '#1e293b' : '#f1f5f9';
    var H          = 300; // fixed chart height for all charts

    function donutChart(el, labels, series, colors) {
        new ApexCharts(document.getElementById(el), {
            chart: { type: 'donut', height: H, toolbar: { show: false } },
            series: series, labels: labels, colors: colors,
            legend: { position: 'bottom', labels: { colors: labelColor } },
            dataLabels: { style: { colors: ['#fff'] } },
            plotOptions: { pie: { donut: { size: '60%' } } },
            tooltip: { y: { formatter: v => v.toLocaleString() } },
        }).render();
    }

    function barChart(el, categories, series, colors, horizontal) {
        new ApexCharts(document.getElementById(el), {
            chart: { type: 'bar', height: H, toolbar: { show: false } },
            series: series,
            xaxis: { categories: categories, labels: { style: { colors: labelColor } } },
            yaxis: { labels: { style: { colors: labelColor } } },
            colors: colors,
            plotOptions: { bar: { horizontal: !!horizontal, borderRadius: 4, columnWidth: '55%', barHeight: '55%' } },
            grid: { borderColor: gridColor },
            dataLabels: { enabled: false },
            legend: { labels: { colors: labelColor } },
            tooltip: { y: { formatter: v => v.toLocaleString() } },
        }).render();
    }

    function stackedBarChart(el, categories, series, colors) {
        new ApexCharts(document.getElementById(el), {
            chart: { type: 'bar', height: H, stacked: true, toolbar: { show: false } },
            series: series,
            xaxis: { categories: categories, labels: { style: { colors: labelColor } } },
            yaxis: { labels: { style: { colors: labelColor } } },
            colors: colors,
            plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
            grid: { borderColor: gridColor },
            dataLabels: { enabled: false },
            legend: { position: 'top', labels: { colors: labelColor } },
            tooltip: { y: { formatter: v => v.toLocaleString() } },
        }).render();
    }

    var palette = ['#6366f1','#f59e0b','#10b981','#ef4444','#3b82f6','#ec4899','#a855f7','#f97316','#14b8a6','#84cc16','#06b6d4','#e11d48'];

    function zoneColors(n) { return Array.from({length:n}, (_,i) => palette[i % palette.length]); }

    // Row 1
    donutChart('chart-gender', ['Male', 'Female'], [gender.male, gender.female], ['#3b82f6', '#ec4899']);
    new ApexCharts(document.getElementById('chart-age'), {
        chart: { type: 'bar', height: H, toolbar: { show: false } },
        series: [{ name: 'Population', data: [age.children, age.youth, age.adult, age.senior] }],
        xaxis: { categories: ['Children (0–12)', 'Youth (13–17)', 'Adult (18–59)', 'Senior (60+)'], labels: { style: { colors: labelColor } } },
        yaxis: { labels: { style: { colors: labelColor } } },
        colors: ['#10b981', '#3b82f6', '#6366f1', '#f59e0b'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%', distributed: true } },
        grid: { borderColor: gridColor },
        dataLabels: { enabled: false },
        legend: { show: false },
        tooltip: { y: { formatter: v => v.toLocaleString() } },
    }).render();
    donutChart('chart-voters', ['Voters', 'Non-Voters'], [voterSplit.voters, voterSplit.non_voters], ['#10b981', '#e2e8f0']);

    // Row 2 — civil status: each bar its own color
    new ApexCharts(document.getElementById('chart-civil'), {
        chart: { type: 'bar', height: H, toolbar: { show: false } },
        series: [{ name: 'Citizens', data: civil.values }],
        xaxis: { categories: civil.labels, labels: { style: { colors: labelColor } } },
        yaxis: { labels: { style: { colors: labelColor } } },
        colors: ['#6366f1','#f59e0b','#10b981','#ef4444','#3b82f6','#ec4899'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%', distributed: true } },
        grid: { borderColor: gridColor },
        dataLabels: { enabled: false },
        legend: { show: false },
        tooltip: { y: { formatter: v => v.toLocaleString() } },
    }).render();
    stackedBarChart('chart-voters-age',
        ['Youth (13–17)', 'Adult (18–59)', 'Senior (60+)'],
        [
            { name: 'Voters',     data: [voterAge.youth_voters,     voterAge.adult_voters,     voterAge.senior_voters] },
            { name: 'Non-Voters', data: [voterAge.youth_non_voters, voterAge.adult_non_voters, voterAge.senior_non_voters] },
        ],
        ['#10b981', '#cbd5e1']
    );

    // Row 2 — voters by zone: each bar its own color
    new ApexCharts(document.getElementById('chart-voters-zone'), {
        chart: { type: 'bar', height: H, toolbar: { show: false } },
        series: [{ name: 'Voters', data: voterZone.values }],
        xaxis: { categories: voterZone.labels, labels: { style: { colors: labelColor } } },
        yaxis: { labels: { style: { colors: labelColor } } },
        colors: zoneColors(voterZone.labels.length),
        plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%', distributed: true } },
        grid: { borderColor: gridColor },
        dataLabels: { enabled: false },
        legend: { show: false },
        tooltip: { y: { formatter: v => v.toLocaleString() } },
    }).render();

    // Row 3 — members by zone: each bar its own color
    new ApexCharts(document.getElementById('chart-zone-members'), {
        chart: { type: 'bar', height: H, toolbar: { show: false } },
        series: [{ name: 'Members', data: zoneMembers.values }],
        xaxis: { categories: zoneMembers.labels, labels: { style: { colors: labelColor } } },
        yaxis: { labels: { style: { colors: labelColor } } },
        colors: zoneColors(zoneMembers.labels.length),
        plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%', distributed: true } },
        grid: { borderColor: gridColor },
        dataLabels: { enabled: false },
        legend: { show: false },
        tooltip: { y: { formatter: v => v.toLocaleString() } },
    }).render();

    barChart('chart-sectors-zone', sectorsZone.labels, [
        { name: 'PWD',          data: sectorsZone.pwd },
        { name: 'Solo Parents', data: sectorsZone.solo_parents },
        { name: 'Senior (60+)', data: sectorsZone.senior },
    ], ['#a855f7', '#f97316', '#14b8a6'], false);

})();
</script>
@endsection
