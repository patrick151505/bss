@extends('layouts.vertical', ['title' => 'Birthday Celebrants', 'sub_title' => 'Citizen Management', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@push('skeleton')
@include('partials._skeleton_table', ['rows' => 10, 'cols' => 5])
@endpush

@section('content')

@php
    $monthNames = [
        1=>'January', 2=>'February', 3=>'March', 4=>'April',
        5=>'May', 6=>'June', 7=>'July', 8=>'August',
        9=>'September', 10=>'October', 11=>'November', 12=>'December',
    ];
    $selectedMonthName = $periodLabel;
    // Table columns: 'default' = shown on first visit; 'locked' = cannot be hidden.
    $tableColumns = [
        'birthday'     => ['label' => 'Birthday',          'default' => true, 'locked' => true],
        'name'         => ['label' => 'Name',              'default' => true, 'locked' => true],
        'age'          => ['label' => 'Age',               'default' => true],
        'gender'       => ['label' => 'Gender',            'default' => false],
        'civil_status' => ['label' => 'Civil Status',      'default' => false],
        'zone'         => ['label' => 'Zone / Area',       'default' => true],
        'contact'      => ['label' => 'Contact',           'default' => true],
        'email'        => ['label' => 'Email',             'default' => false],
        'occupation'   => ['label' => 'Occupation',        'default' => false],
        'voter'        => ['label' => 'Voter / Precinct',  'default' => false],
        'emergency'    => ['label' => 'Emergency Contact', 'default' => false],
        'tags'         => ['label' => 'Tags',              'default' => true],
    ];
    $quarters = ['q1' => 'Q1 (Jan–Mar)', 'q2' => 'Q2 (Apr–Jun)', 'q3' => 'Q3 (Jul–Sep)', 'q4' => 'Q4 (Oct–Dec)'];
@endphp

{{-- Page Header --}}
<div class="flex flex-wrap items-center justify-between gap-4 mb-4">
    <div>
        <h4 class="text-slate-900 dark:text-slate-100 text-lg font-semibold">
            <i class="mgc_cake_line me-2 text-primary"></i>Birthday Celebrants
        </h4>
        <p class="text-sm text-gray-400 mt-0.5">{{ $selectedMonthName }} {{ date('Y') }}</p>
    </div>
    @can('birthdays.export')
    <button type="button" id="open-export-modal"
        class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
        <i class="mgc_file_download_line me-1"></i> Export
    </button>
    @endcan
</div>

{{-- Stats Cards — compact version of the Demographics stat cards --}}
@php
    $pct = fn($n) => $stats['total'] ? round($n / $stats['total'] * 100, 1) : 0;
    $statCards = [
        ['label' => 'Celebrants',        'value' => number_format($stats['total']),
         'sub'   => $selectedMonthName,
         'icon'  => 'mgc_group_line',  'bg' => 'bg-primary/10', 'fg' => 'text-primary'],
        ['label' => 'Celebrating Today', 'value' => $isCurrentMonth ? number_format($stats['today']) : '—',
         'sub'   => $isCurrentMonth ? now()->format('M d, Y') : 'not in this period',
         'icon'  => 'mgc_star_line',   'bg' => 'bg-warning/10', 'fg' => 'text-warning',
         'ring'  => $isCurrentMonth && $stats['today'] > 0],
        ['label' => 'Male',              'value' => number_format($stats['male']),
         'sub'   => $pct($stats['male']) . '% of celebrants',
         'icon'  => 'mgc_male_line',   'bg' => 'bg-blue-100 dark:bg-blue-900/30', 'fg' => 'text-blue-500'],
        ['label' => 'Female',            'value' => number_format($stats['female']),
         'sub'   => $pct($stats['female']) . '% of celebrants',
         'icon'  => 'mgc_female_line', 'bg' => 'bg-pink-100 dark:bg-pink-900/30', 'fg' => 'text-pink-500'],
    ];
@endphp
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    @foreach($statCards as $card)
        <div class="card {{ ($card['ring'] ?? false) ? 'ring-2 ring-warning/50' : '' }}">
            <div class="p-3 flex items-center gap-3 h-full">
                <div class="flex-shrink-0 w-9 h-9 rounded-xl {{ $card['bg'] }} flex items-center justify-center">
                    <i class="{{ $card['icon'] }} text-lg {{ $card['fg'] }}"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $card['label'] }}</p>
                    <h3 class="text-lg font-bold leading-tight text-gray-800 dark:text-gray-100">{{ $card['value'] }}</h3>
                    <p class="text-xs text-gray-400 truncate">{{ $card['sub'] }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Filter --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title"><i class="mgc_filter_line me-2"></i>Filter</h5>
    </div>
    <form method="GET" action="{{ route('birthdays.index') }}">
        <div class="flex flex-wrap items-end gap-4 p-6">

            {{-- Month --}}
            <div>
                <label class="text-gray-700 dark:text-gray-200 text-sm font-medium block mb-1.5">Month / Quarter</label>
                <select name="month" class="form-select w-44">
                    <optgroup label="Monthly">
                        @foreach($monthNames as $num => $name)
                            <option value="{{ $num }}" {{ $month === (string) $num ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Quarterly">
                        @foreach($quarters as $key => $name)
                            <option value="{{ $key }}" {{ $month === $key ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>

            {{-- Voter --}}
            <div>
                <label class="text-gray-700 dark:text-gray-200 text-sm font-medium block mb-1.5">Voter</label>
                <select name="voters" class="form-select w-36">
                    <option value="" {{ $voters === '' ? 'selected' : '' }}>All</option>
                    <option value="1" {{ $voters === '1' ? 'selected' : '' }}>Voters Only</option>
                    <option value="0" {{ $voters === '0' ? 'selected' : '' }}>Non-Voters</option>
                </select>
            </div>

            {{-- PWD --}}
            <div>
                <label class="text-gray-700 dark:text-gray-200 text-sm font-medium block mb-1.5">PWD</label>
                <select name="is_pwd" class="form-select w-36">
                    <option value="" {{ $is_pwd === '' ? 'selected' : '' }}>All</option>
                    <option value="1" {{ $is_pwd === '1' ? 'selected' : '' }}>PWD Only</option>
                    <option value="0" {{ $is_pwd === '0' ? 'selected' : '' }}>Non-PWD</option>
                </select>
            </div>

            {{-- Senior --}}
            <div>
                <label class="text-gray-700 dark:text-gray-200 text-sm font-medium block mb-1.5">Senior (60+)</label>
                <select name="senior" class="form-select w-36">
                    <option value="" {{ $senior === '' ? 'selected' : '' }}>All</option>
                    <option value="1" {{ $senior === '1' ? 'selected' : '' }}>Senior Only</option>
                    <option value="0" {{ $senior === '0' ? 'selected' : '' }}>Non-Senior</option>
                </select>
            </div>

            {{-- Solo Parent --}}
            <div>
                <label class="text-gray-700 dark:text-gray-200 text-sm font-medium block mb-1.5">Solo Parent</label>
                <select name="is_soloparents" class="form-select w-36">
                    <option value="" {{ $soloparents === '' ? 'selected' : '' }}>All</option>
                    <option value="1" {{ $soloparents === '1' ? 'selected' : '' }}>Solo Parent Only</option>
                    <option value="0" {{ $soloparents === '0' ? 'selected' : '' }}>Not Solo Parent</option>
                </select>
            </div>

            {{-- Tags — matches residents with ANY of the checked tags --}}
            @if($allTags->isNotEmpty())
            <div class="w-full">
                <label class="text-gray-700 dark:text-gray-200 text-sm font-medium block mb-1.5">
                    Tags <span class="text-xs font-normal text-gray-400">(any selected)</span>
                </label>
                <div class="flex flex-wrap gap-2">
                    @foreach($allTags as $tag)
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                               {{ in_array($tag->id, $tags) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold text-white"
                              style="background:{{ $tag->color }}">{{ $tag->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Actions --}}
            <div class="flex gap-2 pb-0.5">
                <button type="submit" class="btn bg-primary text-white">
                    <i class="mgc_search_line me-1"></i> Apply
                </button>
                <a href="{{ route('birthdays.index') }}" class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                    <i class="mgc_close_line me-1"></i> Reset
                </a>
            </div>

        </div>
    </form>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header">
        <div class="flex justify-between items-center gap-3">
            <h5 class="card-title">
                {{ $selectedMonthName }} Celebrants
                <span class="ms-2 text-sm font-normal text-gray-400">({{ $citizens->count() }} records)</span>
            </h5>

            {{-- Column picker — defaults match the original layout; choice is remembered per browser --}}
            <div class="relative" id="col-picker">
                <button type="button" id="col-picker-btn"
                    class="btn btn-sm border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
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
                    <button type="button" id="col-reset"
                        class="mt-3 w-full text-xs text-primary hover:underline text-start">
                        <i class="mgc_refresh_2_line me-1"></i>Reset to default
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <div class="min-w-full inline-block align-middle">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th data-col="birthday" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-28">Birthday</th>
                        <th data-col="name" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Name</th>
                        <th data-col="age" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-20">Age</th>
                        <th data-col="gender" class="hidden px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Gender</th>
                        <th data-col="civil_status" class="hidden px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Civil Status</th>
                        <th data-col="zone" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Zone / Area</th>
                        <th data-col="contact" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Contact</th>
                        <th data-col="email" class="hidden px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Email</th>
                        <th data-col="occupation" class="hidden px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Occupation</th>
                        <th data-col="voter" class="hidden px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Voter / Precinct</th>
                        <th data-col="emergency" class="hidden px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Emergency Contact</th>
                        <th data-col="tags" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tags</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($citizens as $citizen)
                    @php
                        $isToday = $isCurrentMonth && $citizen->bday && $citizen->bday->month === $todayMonth && $citizen->bday->day === $todayDay;
                    @endphp
                    <tr class="{{ $isToday
                        ? 'bg-warning/5 dark:bg-warning/10'
                        : 'odd:bg-white even:bg-gray-50 hover:bg-gray-100 dark:odd:bg-slate-800 dark:even:bg-slate-700 dark:hover:bg-slate-600' }}">

                        {{-- Birthday --}}
                        <td data-col="birthday" class="px-4 py-3 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold {{ $isToday ? 'text-warning' : 'text-gray-700 dark:text-gray-200' }}">
                                    {{ $citizen->bday ? $citizen->bday->format('M d') : '—' }}
                                </span>
                                <span class="text-xs text-gray-400">
                                    {{ $citizen->bday ? $citizen->bday->format('Y') : '' }}
                                </span>
                                @if($isToday)
                                    <span class="text-xs font-semibold text-warning bg-warning/10 rounded-full px-1.5 mt-0.5 w-fit">Today</span>
                                @endif
                            </div>
                        </td>

                        {{-- Name --}}
                        <td data-col="name" class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                @if($citizen->profile)
                                    <img src="{{ $citizen->profile_url }}"
                                        class="w-9 h-9 rounded-full object-cover border border-gray-200 dark:border-gray-600 shrink-0"
                                        alt="{{ $citizen->fname }}">
                                @else
                                    <div class="w-9 h-9 rounded-full {{ $isToday ? 'bg-warning/20 text-warning' : 'bg-primary/20 text-primary' }} flex items-center justify-center font-semibold text-sm shrink-0">
                                        {{ strtoupper(substr($citizen->fname, 0, 1)) }}{{ strtoupper(substr($citizen->lname, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                        {{ $citizen->lname }}, {{ $citizen->fname }}
                                        @if($citizen->mname) {{ substr($citizen->mname, 0, 1) }}. @endif
                                        @if($citizen->suffix) <span class="text-gray-400 text-xs">{{ $citizen->suffix }}</span> @endif
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ \App\Models\Setting::instance()->formatCitizenId($citizen->id) }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        {{-- Age --}}
                        <td data-col="age" class="px-4 py-3 whitespace-nowrap">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $citizen->age }}</span>
                            <span class="text-xs text-gray-400 block">yrs old</span>
                        </td>

                        {{-- Gender (optional) --}}
                        <td data-col="gender" class="hidden px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            {{ $citizen->gender == 1 ? 'Male' : 'Female' }}
                        </td>

                        {{-- Civil Status (optional) --}}
                        <td data-col="civil_status" class="hidden px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            {{ $citizen->civilStatus->description ?? '—' }}
                        </td>

                        {{-- Zone --}}
                        <td data-col="zone" class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ $citizen->addressZone->description ?? '—' }}
                            @if($citizen->addressZone && $citizen->addressZone->is_subd == 1 && ($citizen->blk || $citizen->lot))
                                <span class="text-xs text-gray-400 block">
                                    @if($citizen->blk) Blk {{ $citizen->blk }} @endif
                                    @if($citizen->lot) Lot {{ $citizen->lot }} @endif
                                    @if($citizen->phase) Ph {{ $citizen->phase }} @endif
                                </span>
                            @elseif($citizen->no || $citizen->street)
                                <span class="text-xs text-gray-400 block">
                                    {{ trim(($citizen->no ?? '') . ' ' . ($citizen->street ?? '')) }}
                                </span>
                            @endif
                        </td>

                        {{-- Contact --}}
                        <td data-col="contact" class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            {{ $citizen->contact ?: '—' }}
                        </td>

                        {{-- Email (optional) --}}
                        <td data-col="email" class="hidden px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            {{ $citizen->email ?: '—' }}
                        </td>

                        {{-- Occupation (optional) --}}
                        <td data-col="occupation" class="hidden px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ $citizen->occupation ?: '—' }}
                        </td>

                        {{-- Voter / Precinct (optional) --}}
                        <td data-col="voter" class="hidden px-4 py-3 text-sm whitespace-nowrap">
                            @if($citizen->voters)
                                <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                    <span class="w-1.5 h-1.5 inline-block bg-green-400 rounded-full"></span>Voter
                                </span>
                                @if($citizen->pricinct_no)
                                    <span class="text-xs text-gray-400 block mt-0.5">Precinct {{ $citizen->pricinct_no }}</span>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- Emergency Contact (optional) --}}
                        <td data-col="emergency" class="hidden px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            {{ $citizen->ic_fullname ?: '—' }}
                            @if($citizen->ic_contact)
                                <span class="text-xs text-gray-400 block">{{ $citizen->ic_contact }}</span>
                            @endif
                        </td>

                        {{-- Tags --}}
                        <td data-col="tags" class="px-4 py-3 whitespace-nowrap">
                            <div class="flex flex-wrap gap-1">
                                @if($citizen->is_pwd)
                                    <span class="text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 rounded px-1.5 py-0.5">PWD</span>
                                @endif
                                @if($citizen->age >= 60)
                                    <span class="text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300 rounded px-1.5 py-0.5">Senior</span>
                                @endif
                                @if($citizen->is_soloparents)
                                    <span class="text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300 rounded px-1.5 py-0.5">Solo Parent</span>
                                @endif
                                @foreach($citizen->tags as $tag)
                                    <span class="text-xs font-semibold text-white rounded px-1.5 py-0.5" style="background:{{ $tag->color }}">{{ $tag->name }}</span>
                                @endforeach
                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ count($tableColumns) }}" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-2 text-gray-400">
                                <i class="mgc_cake_line text-5xl"></i>
                                <p class="text-sm font-medium">No birthday celebrants found</p>
                                <p class="text-xs">Try selecting a different month/quarter or adjusting your filters.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
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
                <button type="button" id="close-export-modal"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <i class="mgc_close_line text-xl"></i>
                </button>
            </div>

            <form id="export-form" method="POST" action="{{ route('birthdays.export') }}">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="voters" value="{{ $voters }}">
                <input type="hidden" name="is_pwd" value="{{ $is_pwd }}">
                <input type="hidden" name="senior" value="{{ $senior }}">
                <input type="hidden" name="is_soloparents" value="{{ $soloparents }}">
                @foreach($tags as $tagId)
                    <input type="hidden" name="tags[]" value="{{ $tagId }}">
                @endforeach

                <div class="px-6 py-5 space-y-4">

                    <div>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">Month</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedMonthName }} {{ date('Y') }} — {{ $citizens->count() }} celebrants</p>
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
                    <button type="button" id="cancel-export-modal"
                        class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                        Cancel
                    </button>
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
window.addEventListener('DOMContentLoaded', () => {
    const modal     = document.getElementById('export-modal');
    const backdrop  = document.getElementById('export-backdrop');
    const openBtn   = document.getElementById('open-export-modal');
    const closeBtn  = document.getElementById('close-export-modal');
    const cancelBtn = document.getElementById('cancel-export-modal');
    const label     = document.getElementById('export-submit-label');

    function openModal()  { modal.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
    function closeModal() { modal.classList.add('hidden');    document.body.style.overflow = '';       }

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    // ── Column picker ────────────────────────────────────────────────────
    const COL_KEY = 'birthdays.columns';
    const colMenu = document.getElementById('col-picker-menu');
    const toggles = [...document.querySelectorAll('.col-toggle')];

    function applyColumns() {
        toggles.forEach(t => {
            document.querySelectorAll('[data-col="' + t.value + '"]')
                .forEach(el => el.classList.toggle('hidden', !t.checked));
        });
    }
    function saveColumns() {
        try { localStorage.setItem(COL_KEY, JSON.stringify(toggles.filter(t => t.checked).map(t => t.value))); } catch (e) {}
    }
    function setDefaults() {
        toggles.forEach(t => { if (!t.disabled) t.checked = t.dataset.default === '1'; });
    }

    let saved = null;
    try { saved = JSON.parse(localStorage.getItem(COL_KEY)); } catch (e) {}
    if (Array.isArray(saved)) {
        toggles.forEach(t => { if (!t.disabled) t.checked = saved.includes(t.value); });
    } else {
        setDefaults();
    }
    applyColumns();

    toggles.forEach(t => t.addEventListener('change', () => { applyColumns(); saveColumns(); }));
    document.getElementById('col-reset').addEventListener('click', () => {
        setDefaults();
        applyColumns();
        try { localStorage.removeItem(COL_KEY); } catch (e) {}
    });
    document.getElementById('col-picker-btn').addEventListener('click', e => {
        e.stopPropagation();
        colMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', e => {
        if (!document.getElementById('col-picker').contains(e.target)) colMenu.classList.add('hidden');
    });

    // ── Export field shortcuts ───────────────────────────────────────────
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
                didOpen: () => {
                    document.querySelector('.swal2-confirm').style.setProperty('background-color', '#727cf5', 'important');
                },
            });
        }
    });

    document.querySelectorAll('input[name="format"]').forEach(r => {
        r.addEventListener('change', function () {
            label.textContent = 'Export ' + this.value.toUpperCase();
        });
    });
});
</script>
@endsection
