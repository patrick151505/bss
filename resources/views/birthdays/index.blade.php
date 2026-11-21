@extends('layouts.vertical', [
    'title'         => 'Birthdays',
    'sub_title'     => 'Citizens',
    'sub_title_url' => route('citizens.index'),
    'tagline'       => 'Birthday celebrants by month, day or quarter, with today’s and the coming week’s birthdays.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@push('skeleton')
@include('partials._skeleton_table', ['rows' => 10, 'cols' => 5])
@endpush

@php
    use App\Http\Controllers\BirthdayController as BC;

    $year       = now()->year;
    $shortMonth = fn($m) => \Carbon\Carbon::create(null, $m, 1)->format('M');
    $quarters   = ['q1' => 'Q1', 'q2' => 'Q2', 'q3' => 'Q3', 'q4' => 'Q4'];

    // Current filters, carried on every link so changing the period keeps them.
    $filterParams = array_filter([
        'voters'         => $voters,        // always sent ('' = all) so the voters-only default doesn't come back
        'is_pwd'         => $is_pwd,
        'senior'         => $senior,
        'is_soloparents' => $soloparents,
        'tags'           => $tags ?: null,
    ], fn($v) => $v !== null);
    $periodUrl = fn($m) => route('birthdays.index', ['month' => $m] + $filterParams);
    $dayUrl    = fn($m, $d) => route('birthdays.index', ['month' => $m, 'day' => $d] + $filterParams);
    // Month + day (when one is picked), kept on filter links so they don't drop the day.
    $here      = array_filter(['month' => $month, 'day' => $day], fn($v) => $v !== null);

    // Previous / next: day to day (when a day is picked), month to month, or quarter to quarter.
    if ($day) {
        $ref     = \Carbon\Carbon::create(2024, (int) $month, $day);   // leap year, so Feb 29 exists
        $p = $ref->copy()->subDay(); $n = $ref->copy()->addDay();
        $prevUrl = $dayUrl($p->month, $p->day);
        $nextUrl = $dayUrl($n->month, $n->day);
        $prevTitle = 'Previous day (' . $p->format('M j') . ')';
        $nextTitle = 'Next day (' . $n->format('M j') . ')';
    } elseif ($isQuarter) {
        $qn   = (int) substr($month, 1);
        $prev = 'q' . ($qn === 1 ? 4 : $qn - 1);
        $next = 'q' . ($qn === 4 ? 1 : $qn + 1);
    } else {
        $mn   = (int) $month;
        $prev = (string) ($mn === 1 ? 12 : $mn - 1);
        $next = (string) ($mn === 12 ? 1 : $mn + 1);
    }
    if (! $day) {
        $prevUrl = $periodUrl($prev); $nextUrl = $periodUrl($next);
        $prevTitle = 'Previous'; $nextTitle = 'Next';
    }

    // This year's date of a birthday (Feb 29 → Feb 28 when the year has none).
    $thisYear = fn($c) => \Carbon\Carbon::create($year, $c->bday->month, min($c->bday->day, \Carbon\Carbon::create($year, $c->bday->month, 1)->daysInMonth));
    $isToday  = fn($c) => $c->bday && $c->bday->month === $todayMonth && $c->bday->day === $todayDay;
    $canView  = auth()->user()->can('citizens.view');
    $cid      = fn($c) => \App\Models\Setting::instance()->formatCitizenId($c->id);
    $shortName = fn($c) => trim($c->fname . ' ' . $c->lname . ($c->suffix ? ' ' . $c->suffix : ''));

    // This page of the list, grouped by birthday (month + day).
    $groups = $citizens->getCollection()->filter(fn($c) => $c->bday)->groupBy(fn($c) => $c->bday->format('m-d'));
    $faces  = BC::FACES_PER_DAY;

    // Table columns: 'default' = shown on first visit; 'locked' = cannot be hidden.
    $tableColumns = [
        'name'         => ['label' => 'Name',              'default' => true, 'locked' => true],
        'turns'        => ['label' => 'Turns',             'default' => true],
        'gender'       => ['label' => 'Gender',            'default' => false],
        'civil_status' => ['label' => 'Civil Status',      'default' => false],
        'zone'         => ['label' => 'Zone / Area',       'default' => true],
        'contact'      => ['label' => 'Contact',           'default' => true],
        'email'        => ['label' => 'Email',             'default' => false],
        'occupation'   => ['label' => 'Occupation',        'default' => false],
        'voter'        => ['label' => 'Voter / Precinct',  'default' => false],
        'emergency'    => ['label' => 'Emergency Contact', 'default' => false],
        'tags'         => ['label' => 'Groups & Tags',     'default' => true],
    ];

    // Active filters as removable chips.
    $tri = fn($v, $yes, $no) => $v === '1' ? $yes : ($v === '0' ? $no : null);
    $chips = collect([
        'voters'         => $tri($voters, 'Voters only', 'Non-voters'),
        'is_pwd'         => $tri($is_pwd, 'PWD only', 'Non-PWD'),
        'senior'         => $tri($senior, 'Seniors (60+)', 'Below 60'),
        'is_soloparents' => $tri($soloparents, 'Solo parents', 'Not solo parents'),
    ])->filter();
    $chipUrl = fn($key) => route('birthdays.index', array_merge($here + $filterParams, [$key => '']));
    $selectedTags = $allTags->whereIn('id', $tags);

    $pctMale = $stats['total'] ? round($stats['male'] / $stats['total'] * 100) : 0;
@endphp

@section('content')

{{-- ── Period: months with counts, quarters, previous / next ── --}}
<div class="flex flex-wrap items-center gap-3 mb-4">
    <div class="card flex items-center gap-1 p-1.5 min-w-0 flex-1">
        <a href="{{ $prevUrl }}" class="shrink-0 w-8 h-8 rounded-md flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" title="{{ $prevTitle }}" aria-label="{{ $prevTitle }}">
            <i class="mgc_left_line text-lg"></i>
        </a>
        <nav class="flex gap-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden min-w-0 flex-1" aria-label="Month">
            @foreach(range(1, 12) as $m)
            @php $active = !$isQuarter && (int) $month === $m; $n = $monthCounts[$m] ?? 0; @endphp
            <a href="{{ $periodUrl($m) }}" id="{{ $active ? 'month-active' : '' }}"
               style="min-width:3.25rem" class="relative shrink-0 flex flex-col items-center px-2 py-1.5 rounded-md transition
                      {{ $active ? 'bg-primary text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <span class="text-sm font-semibold leading-tight">{{ $shortMonth($m) }}</span>
                <span class="text-[11px] leading-tight {{ $active ? 'text-white/80' : 'text-gray-400' }}">{{ number_format($n) }}</span>
                @if($m === $todayMonth)
                <span class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full {{ $active ? 'bg-white' : 'bg-warning' }}" title="This month"></span>
                @endif
            </a>
            @endforeach
            <span class="shrink-0 w-px my-1 bg-gray-200 dark:bg-gray-700 mx-1"></span>
            @foreach($quarters as $key => $label)
            @php $active = $month === $key; @endphp
            <a href="{{ $periodUrl($key) }}"
               class="shrink-0 flex items-center px-3 rounded-md text-sm font-semibold transition
                      {{ $active ? 'bg-primary text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <a href="{{ $nextUrl }}" class="shrink-0 w-8 h-8 rounded-md flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" title="{{ $nextTitle }}" aria-label="{{ $nextTitle }}">
            <i class="mgc_right_line text-lg"></i>
        </a>
    </div>
    @can('birthdays.export')
    <button type="button" id="open-export-modal" class="btn bg-primary text-white shrink-0">
        <i class="mgc_file_download_line me-1"></i> Export
    </button>
    @endcan
</div>

{{-- ── Day picker (single month only): All days, then each day with its count ── --}}
@if(! $isQuarter)
<div class="card flex items-center gap-2 p-1.5 mb-4">
    <span class="shrink-0 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 hidden sm:block">Day</span>
    <nav class="flex gap-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden min-w-0 flex-1" aria-label="Day of {{ $shortMonth((int) $month) }}">
        <a href="{{ $periodUrl($month) }}" id="{{ $day ? '' : 'day-active' }}"
           class="shrink-0 flex items-center px-3 rounded-md text-sm font-semibold transition
                  {{ $day ? 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' : 'bg-primary text-white' }}">All days</a>
        @foreach(range(1, $daysInMonth) as $d)
        @php
            $active   = $day === $d;
            $n        = $dayCounts[$d] ?? 0;
            $isTodayD = (int) $month === $todayMonth && $d === $todayDay;
        @endphp
        <a href="{{ $dayUrl($month, $d) }}" id="{{ $active ? 'day-active' : '' }}"
           title="{{ $shortMonth((int) $month) }} {{ $d }} — {{ number_format($n) }} {{ Str::plural('celebrant', $n) }}"
           style="min-width:2.5rem" class="relative shrink-0 flex flex-col items-center px-1.5 py-1 rounded-md transition
                  {{ $active ? 'bg-primary text-white' : ($n ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' : 'text-gray-300 dark:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700') }}">
            <span class="text-sm font-semibold leading-tight">{{ $d }}</span>
            <span class="text-[10px] leading-tight {{ $active ? 'text-white/80' : 'text-gray-400' }}">{{ $n ?: '–' }}</span>
            @if($isTodayD)
            <span class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full {{ $active ? 'bg-white' : 'bg-warning' }}" title="Today"></span>
            @endif
        </a>
        @endforeach
    </nav>
</div>
@endif

{{-- ── Summary strip — follows the filters ── --}}
<div class="card mb-6">
    <div class="grid grid-cols-2 lg:grid-cols-5 divide-y lg:divide-y-0 lg:divide-x divide-gray-100 dark:divide-gray-700">
        @foreach([
            ['Celebrants',  $stats['total'],      'mgc_cake_line',     'text-primary', $periodLabel . ' ' . $year],
            ['Today',       $stats['today'],      'mgc_star_line',     'text-warning', now()->format('l, M d')],
            ['Next 7 days', $stats['upcoming'],   'mgc_calendar_line', 'text-info',    now()->addDay()->format('M d') . ' – ' . now()->addDays(7)->format('M d')],
            ['Milestones',  $stats['milestones'], 'mgc_trophy_line',   'text-success', 'turning 18, 60, 80+'],
        ] as $i => [$label, $value, $icon, $fg, $sub])
        <div class="p-5 {{ $i % 2 === 1 ? 'border-l lg:border-l-0 border-gray-100 dark:border-gray-700' : '' }}">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide flex items-center gap-1.5"><i class="{{ $icon }} {{ $fg }} text-base"></i>{{ $label }}</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100 mt-1">{{ number_format($value) }}</p>
            <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $sub }}</p>
        </div>
        @endforeach
        <div class="p-5 col-span-2 lg:col-span-1">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide flex items-center gap-1.5"><i class="mgc_user_3_line text-primary text-base"></i>Male / Female</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100 mt-1">
                {{ number_format($stats['male']) }} <span class="text-gray-300 dark:text-gray-600 font-normal">/</span> {{ number_format($stats['female']) }}
            </p>
            <div class="w-full h-1.5 rounded-full {{ $stats['total'] ? 'bg-pink-400/70' : 'bg-gray-100 dark:bg-gray-700' }} mt-2 overflow-hidden" title="{{ $pctMale }}% male">
                <div class="h-full bg-blue-500" style="width: {{ $pctMale }}%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-1.5">{{ $pctMale }}% male · {{ $stats['total'] ? 100 - $pctMale : 0 }}% female</p>
        </div>
    </div>
</div>

{{-- ── Today + next 7 days (whatever month is selected) ── --}}
<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <div class="card {{ $todayList->isNotEmpty() ? 'ring-2 ring-warning/40' : '' }}">
        <div class="card-header">
            <div class="flex justify-between items-center">
                <h4 class="card-title flex items-center gap-2"><i class="mgc_cake_line text-warning"></i>Today</h4>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ now()->format('M d, Y') }}</span>
            </div>
        </div>
        {{-- Everyone is rendered; past the first $faces they stay hidden until "+N more". --}}
        <div class="p-4" data-more-group>
            <div class="max-h-[560px] overflow-y-auto">
            @forelse($todayList as $c)
            @php $turn = BC::turning($c); $ms = BC::milestone($c); @endphp
            <div class="{{ $loop->index >= $faces ? 'hidden bday-extra' : 'flex' }} items-center gap-3 p-2 rounded-lg {{ $loop->last ? '' : 'mb-1' }} hover:bg-gray-50 dark:hover:bg-gray-700/40" data-show="flex">
                <span class="relative">
                    @include('birthdays._avatar', ['c' => $c, 'size' => 'w-11 h-11 text-sm', 'tone' => 'bg-warning/20 text-warning'])
                    <span class="absolute -bottom-1 -right-1 text-sm" aria-hidden="true">🎂</span>
                </span>
                <div class="min-w-0 flex-1">
                    @if($canView)
                    <a href="{{ route('citizens.show', $c->id) }}" class="block text-sm font-medium text-gray-800 dark:text-gray-100 hover:text-primary truncate">{{ $shortName($c) }}</a>
                    @else
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $shortName($c) }}</p>
                    @endif
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        Turns <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $turn }}</span>
                        @if($c->addressZone) · {{ $c->addressZone->description }}@endif
                    </p>
                    @if($ms)<p class="text-[11px] text-success font-medium truncate" title="{{ $ms }}"><i class="mgc_trophy_line"></i> {{ $ms }}</p>@endif
                </div>
                @if($c->contact)
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $c->contact) }}" class="shrink-0 w-8 h-8 rounded-full bg-success/10 text-success flex items-center justify-center hover:bg-success hover:text-white" title="Call {{ $c->contact }}">
                    <i class="mgc_phone_line"></i>
                </a>
                @endif
            </div>
            @empty
            <div class="py-8 text-center text-gray-400">
                <i class="mgc_cake_line text-4xl opacity-40 block mb-1"></i>
                <p class="text-sm">No birthdays today.</p>
            </div>
            @endforelse
            </div>
            @if($todayList->count() > $faces)
            <button type="button" data-more-toggle
                    data-more-label="+{{ number_format($todayList->count() - $faces) }} more celebrating today"
                    class="block w-full mt-2 text-center text-xs font-medium text-primary hover:underline">
                +{{ number_format($todayList->count() - $faces) }} more celebrating today
            </button>
            @endif
        </div>
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header">
            <div class="flex justify-between items-center">
                <h4 class="card-title flex items-center gap-2"><i class="mgc_calendar_line text-info"></i>Next 7 days</h4>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($upcoming->count()) }} {{ Str::plural('celebrant', $upcoming->count()) }}</span>
            </div>
        </div>
        <div class="p-4">
            @php $upGroups = $upcoming->groupBy(fn($c) => $c->bday->format('m-d')); @endphp
            @forelse($upGroups as $key => $people)
            @php
                $date = $thisYear($people->first());
                if ($date->lt(now()->startOfDay())) $date->addYear();
                $dayLabel = $date->isTomorrow() ? 'Tomorrow' : $date->format('l');
            @endphp
            <div class="flex gap-4 {{ $loop->last ? '' : 'pb-3 mb-3 border-b border-gray-100 dark:border-gray-700' }}">
                <div class="w-14 shrink-0 text-center">
                    <p class="text-[11px] uppercase tracking-wide text-gray-400">{{ $date->format('M') }}</p>
                    <p class="text-xl font-bold leading-none text-gray-800 dark:text-gray-100">{{ $date->format('d') }}</p>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ $dayLabel }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 min-w-0 flex-1" data-more-group>
                    @foreach($people as $c)
                    @php
                        $ms = BC::milestone($c);
                        $chipClass = ($loop->index >= $faces ? 'hidden bday-extra' : 'inline-flex')
                            . ' items-center gap-2 py-1 ps-1 pe-3 rounded-full border '
                            . ($ms ? 'border-success/40 bg-success/5' : 'border-gray-200 dark:border-gray-700');
                    @endphp
                    @if($canView)
                    <a href="{{ route('citizens.show', $c->id) }}" data-show="inline-flex" class="{{ $chipClass }} hover:border-primary/50 hover:bg-primary/5" title="{{ $ms ?: 'Turns ' . BC::turning($c) }}">
                    @else
                    <span data-show="inline-flex" class="{{ $chipClass }}" title="{{ $ms ?: 'Turns ' . BC::turning($c) }}">
                    @endif
                        @include('birthdays._avatar', ['c' => $c, 'size' => 'w-7 h-7 text-[10px]'])
                        <span class="text-sm text-gray-700 dark:text-gray-200 whitespace-nowrap">{{ $shortName($c) }}</span>
                        <span class="text-xs text-gray-400">{{ BC::turning($c) }}</span>
                    {!! $canView ? '</a>' : '</span>' !!}
                    @endforeach
                    @if($people->count() > $faces)
                    <button type="button" data-more-toggle data-more-label="+{{ number_format($people->count() - $faces) }} more"
                            class="text-xs font-medium text-primary hover:underline px-1">+{{ number_format($people->count() - $faces) }} more</button>
                    @endif
                </div>
            </div>
            @empty
            <div class="py-8 text-center text-gray-400">
                <i class="mgc_calendar_line text-4xl opacity-40 block mb-1"></i>
                <p class="text-sm">No birthdays in the next 7 days.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- ── Celebrants of the selected period ── --}}
<div class="card">
    <div class="card-header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h4 class="card-title">
                {{ $periodLabel }} celebrants
                <span class="ms-1 text-sm font-normal text-gray-400">{{ number_format($citizens->total()) }}</span>
            </h4>

            {{-- Column picker — choice is remembered per browser (list view only) --}}
            <div class="relative hidden sm:block" id="col-picker">
                <button type="button" id="col-picker-btn" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">
                    <i class="mgc_layout_grid_line me-1"></i> Columns
                </button>
                <div id="col-picker-menu"
                     class="hidden absolute end-0 mt-2 w-56 z-20 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg p-3">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Show columns</p>
                    <div class="space-y-1.5 max-h-72 overflow-y-auto">
                        @foreach($tableColumns as $key => $col)
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" class="col-toggle form-checkbox rounded text-primary"
                                   value="{{ $key }}" data-default="{{ $col['default'] ? 1 : 0 }}"
                                   {{ ($col['locked'] ?? false) ? 'checked disabled' : '' }}>
                            {{ $col['label'] }}
                        </label>
                        @endforeach
                    </div>
                    <button type="button" id="col-reset" class="mt-3 w-full text-xs text-primary hover:underline text-start">
                        <i class="mgc_refresh_2_line me-1"></i>Reset to default
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters — apply as soon as they change --}}
    <form id="bday-filter" method="GET" action="{{ route('birthdays.index') }}" class="p-4 flex flex-wrap items-center gap-2">
        <input type="hidden" name="month" value="{{ $month }}">
        @if($day)<input type="hidden" name="day" value="{{ $day }}">@endif
        @foreach([
            'voters'         => ['Voters',       $voters,      ['' => 'All voters status', '1' => 'Voters only', '0' => 'Non-voters']],
            'is_pwd'         => ['PWD',          $is_pwd,      ['' => 'PWD: all', '1' => 'PWD only', '0' => 'Non-PWD']],
            'senior'         => ['Senior',       $senior,      ['' => 'Age: all', '1' => 'Seniors (60+)', '0' => 'Below 60']],
            'is_soloparents' => ['Solo parent',  $soloparents, ['' => 'Solo parent: all', '1' => 'Solo parents', '0' => 'Not solo parents']],
        ] as $name => [$aria, $value, $opts])
        <select name="{{ $name }}" class="form-select text-sm w-full sm:w-auto" aria-label="{{ $aria }}" onchange="this.form.requestSubmit()">
            @foreach($opts as $v => $label)
            <option value="{{ $v }}" @selected($value === (string) $v)>{{ $label }}</option>
            @endforeach
        </select>
        @endforeach

        @if($allTags->isNotEmpty())
        <div class="relative" id="tag-picker">
            <button type="button" id="tag-picker-btn" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white h-[38px]">
                <i class="mgc_tag_line me-1"></i>Tags @if($tags)<span class="ms-1 px-1.5 rounded-full bg-primary text-white text-[11px]">{{ count($tags) }}</span>@endif
                <i class="mgc_down_line ms-1"></i>
            </button>
            <div id="tag-picker-menu" class="hidden absolute start-0 mt-2 w-64 z-20 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg p-3">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Has any of these tags</p>
                <div class="space-y-1.5 max-h-64 overflow-y-auto">
                    @foreach($allTags as $tag)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="tags[]" value="{{ $tag->id }}" @checked(in_array($tag->id, $tags)) class="form-checkbox rounded text-primary">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold text-white" style="background:{{ $tag->color }}">{{ $tag->name }}</span>
                    </label>
                    @endforeach
                </div>
                <button type="submit" class="btn btn-sm bg-primary text-white w-full mt-3">Apply tags</button>
            </div>
        </div>
        @endif
    </form>

    @if($chips->isNotEmpty() || $selectedTags->isNotEmpty())
    <div class="px-4 pb-3 -mt-1 flex flex-wrap items-center gap-2">
        @foreach($chips as $key => $label)
        <a href="{{ $chipUrl($key) }}" class="inline-flex items-center gap-1 py-1 px-2.5 rounded-full text-xs font-medium bg-primary/10 text-primary hover:bg-primary/25" title="Remove filter">
            {{ $label }} <i class="mgc_close_line"></i>
        </a>
        @endforeach
        @foreach($selectedTags as $tag)
        <a href="{{ route('birthdays.index', $here + ['tags' => array_values(array_diff($tags, [$tag->id])) ?: null] + array_diff_key($filterParams, ['tags' => 1])) }}"
           class="inline-flex items-center gap-1 py-1 px-2.5 rounded-full text-xs font-semibold text-white hover:opacity-80" style="background:{{ $tag->color }}" title="Remove tag">
            {{ $tag->name }} <i class="mgc_close_line"></i>
        </a>
        @endforeach
        <a href="{{ route('birthdays.index', $here + ['voters' => '']) }}" class="text-xs text-gray-500 dark:text-gray-400 hover:text-danger">Clear all</a>
    </div>
    @endif

    @if($groups->isEmpty())
    <div class="py-16 text-center text-gray-400 border-t border-gray-100 dark:border-gray-700">
        <i class="mgc_cake_line text-5xl opacity-40 block mb-2"></i>
        <p class="text-sm font-medium">No birthday celebrants in {{ $periodLabel }}</p>
        <p class="text-xs mt-1">{{ $chips->isNotEmpty() || $selectedTags->isNotEmpty() ? 'Try removing a filter above.' : 'Pick another month or quarter.' }}</p>
    </div>
    @else

    {{-- ── List view (tablet / desktop) ── --}}
    <div class="hidden sm:block overflow-x-auto border-t border-gray-100 dark:border-gray-700">
        <table class="table min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    @foreach($tableColumns as $key => $col)
                    <th data-col="{{ $key }}" class="{{ $col['default'] ? '' : 'hidden' }} px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">{{ $col['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($groups as $key => $people)
                @php $date = $thisYear($people->first()); $groupToday = $isToday($people->first()); @endphp
                <tr class="{{ $groupToday ? 'bg-warning/10' : 'bg-gray-50/70 dark:bg-gray-800/60' }} border-t border-gray-100 dark:border-gray-700">
                    <td colspan="99" class="px-5 py-2">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="font-semibold uppercase tracking-wide {{ $groupToday ? 'text-warning' : 'text-gray-700 dark:text-gray-200' }}">{{ $date->format('M d') }}</span>
                            <span class="text-gray-400">· {{ $date->format('l') }}</span>
                            @if($groupToday)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-warning text-white font-semibold">🎂 Today</span>
                            @endif
                            <span class="ms-auto text-gray-400">{{ $people->count() }}</span>
                        </div>
                    </td>
                </tr>
                @foreach($people as $c)
                @php $turn = BC::turning($c); $ms = BC::milestone($c); @endphp
                <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40">
                    <td data-col="name" class="px-5 py-3 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            @include('birthdays._avatar', ['c' => $c, 'size' => 'w-9 h-9 text-xs', 'tone' => $groupToday ? 'bg-warning/20 text-warning' : 'bg-primary/15 text-primary'])
                            <div>
                                @if($canView)
                                <a href="{{ route('citizens.show', $c->id) }}" class="font-medium text-gray-800 dark:text-gray-100 hover:text-primary">
                                @else
                                <span class="font-medium text-gray-800 dark:text-gray-100">
                                @endif
                                    {{ $c->lname }}, {{ $c->fname }}@if($c->mname) {{ mb_substr($c->mname, 0, 1) }}.@endif
                                    @if($c->suffix)<span class="text-gray-400 text-xs">{{ $c->suffix }}</span>@endif
                                {!! $canView ? '</a>' : '</span>' !!}
                                <p class="text-xs text-gray-400">{{ $cid($c) }}</p>
                            </div>
                        </div>
                    </td>
                    <td data-col="turns" class="px-5 py-3 whitespace-nowrap">
                        <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $turn }}</span>
                        <span class="text-xs text-gray-400 block">born {{ $c->bday->format('Y') }}</span>
                        @if($ms)
                        <span class="inline-flex items-center gap-1 mt-1 py-0.5 px-2 rounded-full text-[11px] font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300" title="{{ $ms }}">
                            <i class="mgc_trophy_line"></i>Milestone
                        </span>
                        @endif
                    </td>
                    <td data-col="gender" class="hidden px-5 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $c->gender == 1 ? 'Male' : 'Female' }}</td>
                    <td data-col="civil_status" class="hidden px-5 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $c->civilStatus->description ?? '—' }}</td>
                    <td data-col="zone" class="px-5 py-3 text-gray-700 dark:text-gray-300">
                        {{ $c->addressZone->description ?? '—' }}
                        @if($c->addressZone && $c->addressZone->is_subd == 1 && ($c->blk || $c->lot))
                            <span class="text-xs text-gray-400 block">@if($c->blk)Blk {{ $c->blk }} @endif @if($c->lot)Lot {{ $c->lot }} @endif @if($c->phase)Ph {{ $c->phase }}@endif</span>
                        @elseif($c->no || $c->street)
                            <span class="text-xs text-gray-400 block">{{ trim(($c->no ?? '') . ' ' . ($c->street ?? '')) }}</span>
                        @endif
                    </td>
                    <td data-col="contact" class="px-5 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">
                        @if($c->contact)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $c->contact) }}" class="hover:text-primary"><i class="mgc_phone_line text-gray-400 me-1"></i>{{ $c->contact }}</a>
                        @else — @endif
                    </td>
                    <td data-col="email" class="hidden px-5 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $c->email ?: '—' }}</td>
                    <td data-col="occupation" class="hidden px-5 py-3 text-gray-700 dark:text-gray-300">{{ $c->occupation ?: '—' }}</td>
                    <td data-col="voter" class="hidden px-5 py-3 whitespace-nowrap">
                        @if($c->voters)
                            <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                <span class="w-1.5 h-1.5 inline-block bg-green-400 rounded-full"></span>Voter
                            </span>
                            @if($c->pricinct_no)<span class="text-xs text-gray-400 block mt-0.5">Precinct {{ $c->pricinct_no }}</span>@endif
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td data-col="emergency" class="hidden px-5 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">
                        {{ $c->ic_fullname ?: '—' }}
                        @if($c->ic_contact)<span class="text-xs text-gray-400 block">{{ $c->ic_contact }}</span>@endif
                    </td>
                    <td data-col="tags" class="px-5 py-3">
                        @include('birthdays._groups', ['c' => $c])
                    </td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── Card view (phones) ── --}}
    <div class="sm:hidden border-t border-gray-100 dark:border-gray-700">
        @foreach($groups as $key => $people)
        @php $date = $thisYear($people->first()); $groupToday = $isToday($people->first()); @endphp
        <div class="px-4 py-2 flex items-center gap-2 text-xs {{ $groupToday ? 'bg-warning/10' : 'bg-gray-50 dark:bg-gray-800/60' }}">
            <span class="font-semibold uppercase {{ $groupToday ? 'text-warning' : 'text-gray-700 dark:text-gray-200' }}">{{ $date->format('M d') }}</span>
            <span class="text-gray-400">· {{ $date->format('D') }}</span>
            @if($groupToday)<span class="px-2 py-0.5 rounded-full bg-warning text-white font-semibold">🎂 Today</span>@endif
            <span class="ms-auto text-gray-400">{{ $people->count() }}</span>
        </div>
        @foreach($people as $c)
        @php $ms = BC::milestone($c); @endphp
        <div class="px-4 py-3 flex gap-3 border-t border-gray-100 dark:border-gray-700">
            @include('birthdays._avatar', ['c' => $c, 'size' => 'w-10 h-10 text-xs', 'tone' => $groupToday ? 'bg-warning/20 text-warning' : 'bg-primary/15 text-primary'])
            <div class="min-w-0 flex-1">
                <div class="flex justify-between gap-2">
                    @if($canView)
                    <a href="{{ route('citizens.show', $c->id) }}" class="font-medium text-sm text-gray-800 dark:text-gray-100 truncate">{{ $c->lname }}, {{ $c->fname }}</a>
                    @else
                    <span class="font-medium text-sm text-gray-800 dark:text-gray-100 truncate">{{ $c->lname }}, {{ $c->fname }}</span>
                    @endif
                    <span class="shrink-0 text-sm text-gray-700 dark:text-gray-200">Turns <b>{{ BC::turning($c) }}</b></span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $c->addressZone->description ?? '—' }}@if($c->contact) · <a href="tel:{{ preg_replace('/[^0-9+]/', '', $c->contact) }}" class="text-primary">{{ $c->contact }}</a>@endif</p>
                @if($ms)<p class="text-[11px] text-success font-medium mt-0.5"><i class="mgc_trophy_line"></i> {{ $ms }}</p>@endif
                <div class="mt-1.5">@include('birthdays._groups', ['c' => $c])</div>
            </div>
        </div>
        @endforeach
        @endforeach
    </div>
    @endif

    @if($citizens->hasPages())
    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Showing {{ number_format($citizens->firstItem()) }}–{{ number_format($citizens->lastItem()) }} of {{ number_format($citizens->total()) }}
        </p>
        <div>{{ $citizens->links('vendor.pagination.konrix') }}</div>
    </div>
    @endif
</div>

{{-- Export Modal --}}
<div id="export-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div id="export-backdrop" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

    <div class="relative flex items-center justify-center min-h-screen p-4 pointer-events-none">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg flex flex-col pointer-events-auto">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <i class="mgc_file_download_line text-primary text-xl"></i>
                    <h5 class="font-semibold text-gray-800 dark:text-gray-100">Export Birthday List</h5>
                </div>
                <button type="button" id="close-export-modal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <i class="mgc_close_line text-xl"></i>
                </button>
            </div>

            <form id="export-form" method="POST" action="{{ route('birthdays.export') }}" data-no-loader>
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                @if($day)<input type="hidden" name="day" value="{{ $day }}">@endif
                <input type="hidden" name="voters" value="{{ $voters }}">
                <input type="hidden" name="is_pwd" value="{{ $is_pwd }}">
                <input type="hidden" name="senior" value="{{ $senior }}">
                <input type="hidden" name="is_soloparents" value="{{ $soloparents }}">
                @foreach($tags as $tagId)
                    <input type="hidden" name="tags[]" value="{{ $tagId }}">
                @endforeach

                <div class="px-6 py-5 space-y-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">Period</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $periodLabel }} {{ $year }} — {{ number_format($citizens->total()) }} celebrants (all pages)</p>
                        @if($chips->isNotEmpty() || $selectedTags->isNotEmpty())
                        <p class="text-xs text-gray-400 mt-0.5">Filters: {{ $chips->values()->merge($selectedTags->pluck('name'))->implode(' · ') }}</p>
                        @endif
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Format</p>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="format" value="csv" checked class="accent-primary w-4 h-4">
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    <i class="mgc_table_2_line text-green-500 me-1"></i>CSV
                                    <span class="text-xs text-gray-400">(Excel-compatible)</span>
                                </span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="format" value="pdf" class="accent-primary w-4 h-4">
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    <i class="mgc_pdf_line text-red-500 me-1"></i>PDF
                                    <span class="text-xs text-gray-400">(landscape A4)</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/40 rounded-lg p-3">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Fields to include</p>
                            <div class="flex gap-3 text-xs">
                                <button type="button" id="fields-all" class="text-primary hover:underline">Select all</button>
                                <button type="button" id="fields-default" class="text-primary hover:underline">Default</button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 max-h-56 overflow-y-auto">
                            @foreach($exportFields as $key => $label)
                                <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" name="fields[]" value="{{ $key }}"
                                        class="export-field form-checkbox rounded text-primary"
                                        data-default="{{ in_array($key, $defaultExportFields) ? 1 : 0 }}"
                                        {{ in_array($key, $defaultExportFields) ? 'checked' : '' }}>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" id="cancel-export-modal" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</button>
                    <button type="submit" class="btn bg-primary text-white">
                        <i class="mgc_file_download_line me-1"></i>
                        <span id="export-submit-label">Export CSV</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

@endsection

@section('script')
<script>
// "+N more" on Today / Next 7 days: show or hide the rest of that group in place.
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-more-toggle]');
    if (!btn) return;
    const group = btn.closest('[data-more-group]');
    if (!group) return;
    const open = btn.dataset.open !== '1';
    group.querySelectorAll('.bday-extra').forEach(el => {
        el.classList.toggle('hidden', !open);
        el.classList.toggle(el.dataset.show || 'flex', open);
    });
    btn.dataset.open = open ? '1' : '';
    btn.textContent = open ? 'Show less' : btn.dataset.moreLabel;
});

window.addEventListener('DOMContentLoaded', () => {
    // Keep the selected month in view on narrow screens.
    document.getElementById('month-active')?.scrollIntoView({ block: 'nearest', inline: 'center' });
    document.getElementById('day-active')?.scrollIntoView({ block: 'nearest', inline: 'center' });

    // ── Dropdowns (columns, tags) ────────────────────────────────────────
    const dropdowns = [['col-picker', 'col-picker-btn', 'col-picker-menu'], ['tag-picker', 'tag-picker-btn', 'tag-picker-menu']];
    dropdowns.forEach(([wrap, btn, menu]) => {
        document.getElementById(btn)?.addEventListener('click', e => {
            e.stopPropagation();
            dropdowns.forEach(([, , other]) => { if (other !== menu) document.getElementById(other)?.classList.add('hidden'); });
            document.getElementById(menu).classList.toggle('hidden');
        });
    });
    document.addEventListener('click', e => {
        dropdowns.forEach(([wrap, , menu]) => {
            const w = document.getElementById(wrap);
            if (w && !w.contains(e.target)) document.getElementById(menu).classList.add('hidden');
        });
    });

    // ── Column picker ────────────────────────────────────────────────────
    const COL_KEY = 'birthdays.columns.v2';
    const toggles = [...document.querySelectorAll('.col-toggle')];
    const applyColumns = () => toggles.forEach(t =>
        document.querySelectorAll('[data-col="' + t.value + '"]').forEach(el => el.classList.toggle('hidden', !t.checked)));
    const saveColumns = () => { try { localStorage.setItem(COL_KEY, JSON.stringify(toggles.filter(t => t.checked).map(t => t.value))); } catch (e) {} };
    const setDefaults = () => toggles.forEach(t => { if (!t.disabled) t.checked = t.dataset.default === '1'; });

    let saved = null;
    try { saved = JSON.parse(localStorage.getItem(COL_KEY)); } catch (e) {}
    if (Array.isArray(saved)) toggles.forEach(t => { if (!t.disabled) t.checked = saved.includes(t.value); });
    else setDefaults();
    applyColumns();
    toggles.forEach(t => t.addEventListener('change', () => { applyColumns(); saveColumns(); }));
    document.getElementById('col-reset')?.addEventListener('click', () => {
        setDefaults(); applyColumns();
        try { localStorage.removeItem(COL_KEY); } catch (e) {}
    });

    // ── Export modal ─────────────────────────────────────────────────────
    const modal = document.getElementById('export-modal');
    const openBtn = document.getElementById('open-export-modal');
    if (!openBtn) return;   // no export permission
    const openModal  = () => { modal.classList.remove('hidden'); document.body.style.overflow = 'hidden'; };
    const closeModal = () => { modal.classList.add('hidden');    document.body.style.overflow = ''; };
    openBtn.addEventListener('click', openModal);
    ['close-export-modal', 'cancel-export-modal', 'export-backdrop'].forEach(id => document.getElementById(id).addEventListener('click', closeModal));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    const fieldBoxes = [...document.querySelectorAll('.export-field')];
    document.getElementById('fields-all').addEventListener('click', () => fieldBoxes.forEach(f => f.checked = true));
    document.getElementById('fields-default').addEventListener('click', () => fieldBoxes.forEach(f => f.checked = f.dataset.default === '1'));
    document.getElementById('export-form').addEventListener('submit', e => {
        if (!fieldBoxes.some(f => f.checked)) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'No fields selected',
                text: 'Pick at least one field to export.',
                didOpen: () => document.querySelector('.swal2-confirm').style.setProperty('background-color', '#3073F1', 'important'),
            });
            return;
        }
        setTimeout(closeModal, 300);
    });
    document.querySelectorAll('input[name="format"]').forEach(r => r.addEventListener('change', function () {
        document.getElementById('export-submit-label').textContent = 'Export ' + this.value.toUpperCase();
    }));
});
</script>
@endsection
