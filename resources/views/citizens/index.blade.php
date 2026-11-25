@extends('layouts.vertical', [
    'title'         => 'Citizens',
    'sub_title'     => 'Citizen Management',
    'sub_title_url' => route('citizens.index'),
    'tagline'       => 'Search, filter and manage resident records.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@push('skeleton')
@include('partials._skeleton_table', ['rows' => 10, 'cols' => 6])
@endpush

@section('css')
{{-- noUiSlider's base stylesheet is REQUIRED: it supplies the absolute
     positioning and transforms that place the handles along the track. Without
     it both handles collapse to the far left. The rules below only restyle it
     to match the theme. --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nouislider@15.7.1/dist/nouislider.min.css">
<style>
/* Age-range slider — theme overrides on top of the base stylesheet above. */
/* Slimmer than the library default (18px) — the handles overhang the track,
   which is why the wrapper around #age-slider carries vertical padding. */
#age-slider {
    height: 4px;
    border: 0;
    border-radius: 9999px;
    background: #e5e7eb;
    box-shadow: none;
    margin: 6px 0 10px;
}
.dark #age-slider { background: #374151; }

#age-slider .noUi-connect { background: #3073F1; }          /* matches `primary` */
#age-slider .noUi-connects { border-radius: 9999px; }

/* The base sheet positions horizontal handles with `left` (it sets
   right:auto), so the offset must be set on `left` — half the handle width,
   to centre it on the handle's origin. top centres it on the 4px track. */
#age-slider .noUi-handle {
    width: 16px; height: 16px;
    left: -8px; right: auto;
    top: -6px;
    border: 2px solid #3073F1;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 3px rgb(0 0 0 / .2);
    cursor: grab;
}
#age-slider .noUi-handle:active { cursor: grabbing; }
#age-slider .noUi-handle:focus  { outline: none; box-shadow: 0 0 0 3px rgb(48 115 241 / .25); }
.dark #age-slider .noUi-handle  { background: #1f2937; }

/* nouislider draws decorative grips inside each handle; remove them at this size. */
#age-slider .noUi-handle::before,
#age-slider .noUi-handle::after { display: none; }

/* Give the handles room so they are comfortable on touch. */
#age-slider .noUi-touch-area { transform: scale(2.2); border-radius: 50%; }

/* The base sheet ships a light border + inset shadow on the target and a
   "not-allowed" cursor; neither suits this control. */
#age-slider.noUi-target { border: 0; box-shadow: none; cursor: default; }
</style>
@endsection

@section('content')
<div class="grid grid-cols-12 gap-6">
    <div class="col-span-12">

        {{-- Flash messages --}}
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
        @if($errors->any())
        <div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30">
            <ul class="list-disc list-inside text-sm text-danger/80 space-y-0.5">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
        @endif

        {{-- Action buttons. The page heading comes from the layout's `tagline`,
             so there is no <h4> here — it would duplicate the title.
             whitespace-nowrap keeps each label on one line; the row wraps as a
             whole instead of each button breaking its own text. --}}
        <div class="flex flex-wrap items-center gap-2 mb-4">
            @can('citizens.export')
            <button type="button" id="open-export-modal"
                class="btn whitespace-nowrap px-3 py-1.5 text-xs sm:px-4 sm:py-2 sm:text-sm border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                <i class="mgc_file_download_line me-1"></i> Export
            </button>
            @endcan
            @can('citizens.create')
            <a href="{{ route('citizens.create') }}"
               class="btn whitespace-nowrap px-3 py-1.5 text-xs sm:px-4 sm:py-2 sm:text-sm bg-primary text-white">
                <i class="mgc_add_line me-1"></i> Add Citizen
            </a>
            <a href="{{ route('citizens.create-minor') }}"
               class="btn whitespace-nowrap px-3 py-1.5 text-xs sm:px-4 sm:py-2 sm:text-sm bg-amber-500 text-white">
                <i class="mgc_user_2_line me-1"></i> Register Minor
            </a>
            @endcan
        </div>

        {{-- Quick-filter count cards. Counts follow the other filters (zone,
             search, tags…); a click shows just that group and keeps them. --}}
        @php
            $statCards = [
                ['key' => 'all',     'label' => 'All citizens', 'n' => $stats['total'],   'icon' => 'mgc_group_line',       'tone' => 'primary'],
                ['key' => 'voters',  'label' => 'Voters',       'n' => $stats['voters'],  'icon' => 'mgc_user_follow_line', 'tone' => 'success'],
                ['key' => 'seniors', 'label' => 'Seniors (60+)','n' => $stats['seniors'], 'icon' => 'mgc_walk_line',        'tone' => 'warning'],
                ['key' => 'pwd',     'label' => 'PWD',          'n' => $stats['pwd'],     'icon' => 'mgc_heart_line',       'tone' => 'info'],
                ['key' => 'solo',    'label' => 'Solo parents', 'n' => $stats['solo'],    'icon' => 'mgc_user_2_line',      'tone' => 'danger'],
                ['key' => 'minors',  'label' => 'Minors (0–17)','n' => $stats['minors'],  'icon' => 'mgc_baby_line',        'tone' => 'dark'],
            ];
            $toneCls = [
                'primary' => 'bg-primary/10 text-primary', 'success' => 'bg-success/10 text-success',
                'warning' => 'bg-warning/10 text-warning', 'info' => 'bg-info/10 text-info',
                'danger'  => 'bg-danger/10 text-danger',   'dark' => 'bg-dark/10 text-slate-700 dark:text-slate-200',
            ];
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3 mb-4" id="stat-cards">
            @foreach($statCards as $c)
            <button type="button" data-quick="{{ $c['key'] }}"
                    class="stat-card card p-3 sm:p-4 text-start flex items-center gap-3 border-2 border-transparent hover:border-primary/40 transition"
                    title="Show {{ strtolower($c['label']) }}">
                <span class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 {{ $toneCls[$c['tone']] }}">
                    <i class="{{ $c['icon'] }} text-xl"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-[11px] leading-tight text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $c['label'] }}</span>
                    <span class="block text-xl font-bold text-gray-800 dark:text-gray-100" data-stat="{{ $c['key'] }}">{{ number_format($c['n']) }}</span>
                </span>
            </button>
            @endforeach
        </div>

        {{-- Advanced Filter Card --}}
        @php
            // Search has its own always-visible box, and voters is applied by
            // default — neither should make the advanced panel look "active"
            // or force it open.
            $activeFilters = collect($filters ?? [])
                ->reject(fn($v, $k) => $k === 'search')
                ->reject(fn($v, $k) => $k === 'voters' && ! request()->has('voters'))
                ->filter(fn($v) => $v !== '' && $v !== null && $v !== [])
                ->count();
        @endphp
        <div class="card mb-4">
            {{-- Search stays visible whether or not the advanced panel is open --}}
            <div class="p-4 border-b dark:border-gray-700">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative flex-1 min-w-[220px]">
                        <input type="text" id="quick-search" value="{{ $filters['search'] ?? '' }}"
                            class="form-input ps-9 w-full"
                            placeholder="Search name, contact, email… or scan an ID" autocomplete="off">
                        <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <span class="hidden md:inline-flex items-center gap-1 text-xs text-gray-400 shrink-0" title="Scan a Barangay ID with a handheld scanner anytime on this page to see that citizen's activity">
                        <i class="mgc_qrcode_line"></i> Scan an ID, or click a row → All Transactions, to see all activity
                    </span>
                    <button type="button" id="quick-search-btn" class="btn bg-primary text-white shrink-0">
                        <i class="mgc_search_line"></i> Search
                    </button>
                    <button type="button" id="filter-toggle" class="btn bg-dark/10 text-slate-700 dark:text-slate-300 hover:bg-dark/20 shrink-0">
                        <i class="mgc_filter_line"></i> Advanced Filter
                        <span id="filter-active-badge" class="ms-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-primary rounded-full {{ $activeFilters > 0 ? '' : 'hidden' }}">{{ $activeFilters }}</span>
                        <i class="mgc_down_line transition-transform duration-200" id="filter-chevron"></i>
                    </button>

                    {{-- Saved filters --}}
                    <div class="relative shrink-0" id="saved-filters">
                        <button type="button" id="saved-filters-btn" class="btn bg-dark/10 text-slate-700 dark:text-slate-300 hover:bg-dark/20" aria-haspopup="true" aria-expanded="false">
                            <i class="mgc_bookmark_line"></i> Saved filters
                            <i class="mgc_down_line"></i>
                        </button>
                        <div id="saved-filters-menu" class="hidden absolute end-0 mt-2 w-72 max-w-[calc(100vw-2rem)] z-30 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                            <div id="saved-filters-list" class="max-h-72 overflow-y-auto py-1"></div>
                            <div class="border-t border-gray-200 dark:border-gray-700 p-2">
                                <button type="button" id="save-filter-btn" class="w-full btn btn-sm bg-primary/10 text-primary hover:bg-primary hover:text-white justify-center">
                                    <i class="mgc_save_line"></i> Save current filters…
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Summary of what is filtered, shown while the panel is closed --}}
            <div id="filter-chips" class="hidden px-4 py-3 border-b dark:border-gray-700 flex flex-wrap items-center gap-2"></div>

            <div id="filter-body" class="{{ ($activeFilters ?? 0) > 0 ? '' : 'hidden' }}">
                <form method="GET" action="{{ route('citizens.index') }}" id="filter-form">
                    <div class="grid lg:grid-cols-4 md:grid-cols-2 grid-cols-1 gap-4 p-6">

                        {{-- Search — mirrored from the always-visible box above --}}
                        <input type="hidden" name="search" id="search-field" value="{{ $filters['search'] ?? '' }}">

                        {{-- Gender --}}
                        <div>
                            <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">All</option>
                                <option value="1" {{ ($filters['gender'] ?? '') == '1' ? 'selected' : '' }}>Male</option>
                                <option value="2" {{ ($filters['gender'] ?? '') == '2' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>

                        {{-- Civil Status --}}
                        <div>
                            <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Civil Status</label>
                            <select name="civil_status" class="form-select">
                                <option value="">All</option>
                                @foreach($civilStatuses as $cs)
                                    <option value="{{ $cs->id }}" {{ ($filters['civil_status'] ?? '') == $cs->id ? 'selected' : '' }}>
                                        {{ $cs->description }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Address / Zone --}}
                        <div>
                            <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Address / Zone</label>
                            <select name="address" class="form-select">
                                <option value="">All</option>
                                @foreach($addresses as $addr)
                                    <option value="{{ $addr->id }}" {{ ($filters['address'] ?? '') == $addr->id ? 'selected' : '' }}>
                                        {{ $addr->description }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Approval Status --}}
                        <div>
                            <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Approval Status</label>
                            <select name="approval_status" class="form-select">
                                <option value="">All</option>
                                @foreach($approvalStatuses as $as)
                                    <option value="{{ $as->id }}" {{ ($filters['approval_status'] ?? '') == $as->id ? 'selected' : '' }}>
                                        {{ $as->description }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Age Range — dual-handle slider.
                             The hidden inputs are what actually submit, so the
                             server contract (age_min / age_max) is unchanged.
                             Left empty when the range is untouched, so "0–100"
                             is not mistaken for a real filter. --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-gray-800 dark:text-gray-200 text-sm font-medium">Age Range</label>
                                <span id="age-range-label" class="text-xs font-medium text-gray-400">All ages</span>
                            </div>

                            <input type="hidden" name="age_min" id="age-min" value="{{ $filters['age_min'] ?? '' }}">
                            <input type="hidden" name="age_max" id="age-max" value="{{ $filters['age_max'] ?? '' }}">

                            {{-- Type exact ages (e.g. 18 to 25). Works even if the slider
                                 library could not load. --}}
                            <div class="flex items-center gap-2">
                                <input type="number" id="age-from" min="0" max="120" placeholder="From"
                                       value="{{ $filters['age_min'] ?? '' }}" class="form-input text-sm w-full" aria-label="Age from">
                                <span class="text-gray-400 text-sm">to</span>
                                <input type="number" id="age-to" min="0" max="120" placeholder="To"
                                       value="{{ $filters['age_max'] ?? '' }}" class="form-input text-sm w-full" aria-label="Age to">
                            </div>

                            <div class="px-1 pt-4">
                                <div id="age-slider"></div>
                            </div>

                            <div class="flex flex-wrap gap-1.5 mt-4">
                                @foreach([['0–17', 0, 17], ['18–25', 18, 25], ['15–30 (SK)', 15, 30], ['18–59', 18, 59], ['60+', 60, '']] as [$lbl, $lo, $hi])
                                <button type="button" onclick="setAgeRange({{ $lo }}, '{{ $hi }}')"
                                        class="age-preset px-2 py-0.5 rounded-full text-[11px] font-medium border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-primary hover:text-primary">{{ $lbl }}</button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Household Search --}}
                        {{-- One column like every other field: spanning two
                             pushed the following fields out of step and left
                             "Household Assignment" stranded on its own row. --}}
                        <div>
                            <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Household</label>
                            <div class="relative">
                                <input type="text" name="household_search" id="hh-filter-input"
                                    value="{{ $filters['household_search'] ?? '' }}"
                                    class="form-input ps-9"
                                    placeholder="Search by ID or address…"
                                    autocomplete="off">
                                <i class="mgc_home_3_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>

                        {{-- Household Role --}}
                        <div>
                            <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Role in Household</label>
                            <select name="hh_role" class="form-select">
                                <option value="">All</option>
                                <option value="2" {{ ($filters['hh_role'] ?? '') == '2' ? 'selected' : '' }}>Head of Household</option>
                                <option value="1" {{ ($filters['hh_role'] ?? '') == '1' ? 'selected' : '' }}>Head of Family</option>
                                <option value="0" {{ ($filters['hh_role'] ?? '') == '0' ? 'selected' : '' }}>Member</option>
                            </select>
                        </div>

                        {{-- Household Assignment --}}
                        <div>
                            <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Household Assignment</label>
                            <select name="hh_assigned" class="form-select">
                                <option value="">All</option>
                                <option value="1" {{ ($filters['hh_assigned'] ?? '') == '1' ? 'selected' : '' }}>Assigned</option>
                                <option value="0" {{ ($filters['hh_assigned'] ?? '') == '0' ? 'selected' : '' }}>Not Assigned</option>
                            </select>
                        </div>

                        {{-- Tags --}}
                        @if($allTags->isNotEmpty())
                        @php $selectedTagIds = array_map('intval', array_filter((array)($filters['tags'] ?? []))); @endphp
                        <div class="lg:col-span-4">
                            <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Tags</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach($allTags as $tag)
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                           {{ in_array($tag->id, $selectedTagIds) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-primary focus:ring-primary">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold text-white"
                                          style="background:{{ $tag->color }}">{{ $tag->name }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Flags Row --}}
                        {{-- Nested grid on the same 4-column track as the panel
                             above, so these selects line up with Gender / Civil
                             Status / etc. A flex row sized them to their content
                             and left the column edges ragged. --}}
                        <div class="lg:col-span-4 grid lg:grid-cols-4 md:grid-cols-2 grid-cols-1 gap-4">

                            {{-- Active/Inactive filter removed: the list always
                                 shows active citizens only. --}}

                            {{-- Voter --}}
                            <div>
                                <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Voter</label>
                                <select name="voters" class="form-select">
                                    <option value="">All</option>
                                    <option value="1" {{ ($filters['voters'] ?? '') == '1' ? 'selected' : '' }}>Voter</option>
                                    <option value="0" {{ ($filters['voters'] ?? '') == '0' ? 'selected' : '' }}>Non-Voter</option>
                                </select>
                            </div>

                            {{-- PWD --}}
                            <div>
                                <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">PWD</label>
                                <select name="is_pwd" class="form-select">
                                    <option value="">All</option>
                                    <option value="1" {{ ($filters['is_pwd'] ?? '') == '1' ? 'selected' : '' }}>PWD</option>
                                    <option value="0" {{ ($filters['is_pwd'] ?? '') == '0' ? 'selected' : '' }}>Non-PWD</option>
                                </select>
                            </div>

                            {{-- Solo Parent --}}
                            <div>
                                <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Solo Parent</label>
                                <select name="is_soloparents" class="form-select">
                                    <option value="">All</option>
                                    <option value="1" {{ ($filters['is_soloparents'] ?? '') == '1' ? 'selected' : '' }}>Solo Parent</option>
                                    <option value="0" {{ ($filters['is_soloparents'] ?? '') == '0' ? 'selected' : '' }}>Not Solo Parent</option>
                                </select>
                            </div>

                            {{-- Senior Citizen (age >= 60) --}}
                            <div>
                                <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Senior Citizen</label>
                                <select name="senior" class="form-select">
                                    <option value="">All</option>
                                    <option value="1" {{ ($filters['senior'] ?? '') == '1' ? 'selected' : '' }}>Senior (60+)</option>
                                    <option value="0" {{ ($filters['senior'] ?? '') == '0' ? 'selected' : '' }}>Non-Senior</option>
                                </select>
                            </div>

                            {{-- Income class (optional — only when Income Classification is on) --}}
                            @if(\App\Support\IncomeClass::visibleTo(auth()->user()))
                            <div>
                                <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Family Income Class</label>
                                <select name="income_class" class="form-select">
                                    <option value="">All</option>
                                    @foreach(\App\Support\IncomeClass::BANDS as $key => $band)
                                        <option value="{{ $key }}" {{ ($filters['income_class'] ?? '') === $key ? 'selected' : '' }}>{{ $band['label'] }}</option>
                                    @endforeach
                                    <option value="none" {{ ($filters['income_class'] ?? '') === 'none' ? 'selected' : '' }}>Not assessed</option>
                                </select>
                            </div>
                            @endif

                        </div>

                        {{-- Filter Actions --}}
                        <div class="lg:col-span-4 flex justify-end gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" id="clear-filters-btn" class="btn border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">
                                <i class="mgc_close_line me-1"></i> Clear
                            </button>
                            <button type="button" id="apply-filters-btn" class="btn bg-primary text-white">
                                <i class="mgc_search_line me-1"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Results Card --}}
        <div class="card">
            <div class="card-header">
                <div class="flex flex-wrap justify-between items-center gap-3">
                    <div>
                        <h5 class="card-title">Citizen List</h5>
                    </div>
                    <div class="flex items-center gap-2">
                        {{-- Column chooser (table view, tablet and up) — remembered per user in this browser --}}
                        @php
                            $listColumns = [
                                'household' => 'Household', 'age' => 'Age', 'gender' => 'Gender',
                                'address' => 'Address / Zone', 'civil' => 'Civil Status', 'tags' => 'Tags',
                                'flags' => 'Flags', 'status' => 'Status', 'registered' => 'Registered',
                                'updated' => 'Last Updated',
                            ];
                        @endphp
                        <div class="relative hidden md:block" id="col-chooser">
                            <button type="button" id="col-chooser-btn" title="Choose columns"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:text-primary transition">
                                <i class="mgc_column_line text-base"></i> Columns
                            </button>
                            <div id="col-chooser-menu" class="hidden absolute end-0 mt-2 w-56 z-30 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg p-2">
                                <p class="px-2 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Show columns</p>
                                @foreach($listColumns as $key => $label)
                                <label class="flex items-center gap-2 px-2 py-1.5 rounded cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 text-sm text-gray-700 dark:text-gray-200">
                                    <input type="checkbox" class="col-toggle form-checkbox rounded text-primary" value="{{ $key }}" checked> {{ $label }}
                                </label>
                                @endforeach
                                <button type="button" id="col-reset" class="w-full mt-1 text-xs text-primary hover:underline py-1">Show all</button>
                            </div>
                        </div>
                        {{-- Table / Grid toggle — choice is remembered per browser --}}
                        <div class="inline-flex shrink-0 rounded-md border border-gray-300 dark:border-gray-600 overflow-hidden" role="group" aria-label="View">
                            <button type="button" data-view="table" title="Table view"
                                class="view-toggle px-2.5 py-1.5 text-gray-500 dark:text-gray-400 hover:text-primary transition">
                                <i class="mgc_table_2_line text-base"></i>
                            </button>
                            <button type="button" data-view="grid" title="Grid view"
                                class="view-toggle px-2.5 py-1.5 border-s border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:text-primary transition">
                                <i class="mgc_grid_line text-base"></i>
                            </button>
                        </div>
                        <select id="per-page-select" class="form-select w-auto text-sm py-1.5">
                            @foreach([15, 25, 50, 100] as $pp)
                                <option value="{{ $pp }}" {{ request('per_page', 15) == $pp ? 'selected' : '' }}>{{ $pp }} / page</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Bulk actions — shown while citizens are ticked (kept across pages) --}}
            <div id="bulk-bar" class="hidden sticky top-0 z-20 px-4 py-2.5 border-b border-primary/30 bg-primary/10 flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold text-primary me-1"><i class="mgc_check_line"></i> <span id="bulk-count">0</span> selected</span>
                @can('citizens.tag')
                @if($allTags->isNotEmpty())
                <button type="button" id="bulk-tag-add" class="btn btn-sm bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-200 border border-gray-200 dark:border-gray-700 hover:text-primary">
                    <i class="mgc_tag_line"></i> Add tag
                </button>
                <button type="button" id="bulk-tag-remove" class="btn btn-sm bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-200 border border-gray-200 dark:border-gray-700 hover:text-primary">
                    <i class="mgc_close_line"></i> Remove tag
                </button>
                @endif
                @endcan
                @can('citizens.export')
                <button type="button" id="bulk-export" class="btn btn-sm bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-200 border border-gray-200 dark:border-gray-700 hover:text-primary">
                    <i class="mgc_file_download_line"></i> Export selected
                </button>
                @endcan
                @can('citizens.archive')
                <button type="button" id="bulk-archive" class="btn btn-sm bg-warning/25 text-warning hover:bg-warning hover:text-white">
                    <i class="mgc_archive_line"></i> Set inactive
                </button>
                @endcan
                <button type="button" id="bulk-clear" class="ms-auto text-xs font-medium text-gray-600 dark:text-gray-300 hover:text-danger hover:underline">
                    Clear selection
                </button>
            </div>

            {{-- Table wrapper (swapped by AJAX) --}}
            <div id="citizens-table-wrap" class="relative">
                {{-- Loading overlay --}}
                <div id="table-loading" class="absolute inset-0 bg-white/60 dark:bg-gray-800/60 flex items-center justify-center z-10 hidden rounded-b-lg">
                    <div class="flex items-center gap-2 text-primary text-sm font-medium">
                        <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        Loading…
                    </div>
                </div>
                @include('citizens._table', ['citizens' => $citizens])
            </div>
        </div>

    </div>
</div>

{{-- Archive (set inactive) form — posts to citizens.destroy, which
     deactivates rather than deleting. --}}
<form id="delete-form" method="POST" action="" class="hidden">
    @csrf
    @method('DELETE')
    <input type="hidden" name="reason" id="archive-reason">
</form>

{{-- Selected-citizen action bar. Docks at the bottom so every action is
     reachable without scrolling the wide table sideways. --}}
<div id="action-bar"
     class="fixed inset-x-0 bottom-0 z-40 translate-y-full opacity-0 transition-all duration-200 pointer-events-none">
    <div class="mx-auto max-w-5xl m-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-2xl">
        <div class="flex flex-wrap items-center gap-3 p-3">

            {{-- Who is selected --}}
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <div id="ab-avatar" class="w-10 h-10 rounded-full bg-primary/20 text-primary flex items-center justify-center font-semibold shrink-0 overflow-hidden"></div>
                <div class="min-w-0">
                    <p id="ab-name" class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate"></p>
                    <p id="ab-code" class="text-xs font-mono text-gray-400 truncate"></p>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" id="ab-activity" class="btn btn-sm bg-info/25 text-info hover:bg-info hover:text-white">
                    <i class="mgc_history_line"></i> All Transactions
                </button>
                <a id="ab-view" href="#" class="btn btn-sm bg-primary text-white">
                    <i class="mgc_eye_line"></i> View
                </a>
                @can('citizens.edit')
                <a id="ab-edit" href="#" class="btn btn-sm bg-dark/10 text-slate-700 dark:text-slate-200 hover:bg-dark/20">
                    <i class="mgc_edit_line"></i> Edit
                </a>
                @endcan
                @can('citizen_ids.view')
                <a id="ab-id" href="#" class="btn btn-sm bg-dark/10 text-slate-700 dark:text-slate-200 hover:bg-dark/20">
                    <i class="mgc_IDcard_line"></i> Barangay ID
                </a>
                @endcan
                @can('documents.create')
                <a id="ab-doc" href="#" class="btn btn-sm bg-dark/10 text-slate-700 dark:text-slate-200 hover:bg-dark/20">
                    <i class="mgc_document_line"></i> New Document
                </a>
                @endcan
                @can('citizens.archive')
                {{-- btn-delete is what the confirmation handler listens for. --}}
                <button type="button" id="ab-delete" class="btn-delete btn btn-sm bg-warning/25 text-warning hover:bg-warning hover:text-white">
                    <i class="mgc_archive_line"></i> Set Inactive
                </button>
                @endcan
                <button type="button" id="ab-close" class="p-1.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" title="Close">
                    <i class="mgc_close_line text-lg"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Quick view — slides in from the right when a citizen's name is clicked --}}
<div id="qv" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="qv-name">
    <div id="qv-backdrop" class="absolute inset-0 bg-black/40"></div>
    <aside id="qv-panel" class="absolute inset-y-0 end-0 w-full sm:w-[420px] bg-white dark:bg-gray-800 shadow-2xl flex flex-col translate-x-full transition-transform duration-200">
        <div class="flex items-center justify-between px-5 py-3 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Quick view</h3>
            <button type="button" id="qv-close" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" aria-label="Close"><i class="mgc_close_line text-xl"></i></button>
        </div>
        <div id="qv-body" class="flex-1 overflow-y-auto"></div>
        <div class="flex flex-wrap gap-2 justify-end px-5 py-3 border-t dark:border-gray-700">
            <button type="button" id="qv-activity" class="btn btn-sm bg-info/25 text-info hover:bg-info hover:text-white"><i class="mgc_history_line"></i> All transactions</button>
            @can('citizens.edit')
            <a id="qv-edit" href="#" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white"><i class="mgc_edit_line"></i> Edit</a>
            @endcan
            <a id="qv-profile" href="#" class="btn btn-sm bg-primary text-white"><i class="mgc_user_3_line"></i> Full profile</a>
        </div>
    </aside>
</div>

{{-- ── Export Modal ── --}}
@php
$exportFieldGroups = [
    'Personal Information' => [
        'citizen_id'  => ['label' => 'Citizen ID',    'default' => true],
        'lname'       => ['label' => 'Last Name',      'default' => true],
        'fname'       => ['label' => 'First Name',     'default' => true],
        'mname'       => ['label' => 'Middle Name',    'default' => false],
        'suffix'      => ['label' => 'Suffix',         'default' => false],
        'gender'      => ['label' => 'Gender',         'default' => true],
        'civil_status'=> ['label' => 'Civil Status',   'default' => true],
        'bday'        => ['label' => 'Birthday',       'default' => true],
        'age'         => ['label' => 'Age',            'default' => true],
        'bplace'      => ['label' => 'Place of Birth', 'default' => false],
        'citizenship' => ['label' => 'Citizenship',    'default' => false],
        'occupation'  => ['label' => 'Occupation',     'default' => false],
    ],
    'Address' => [
        'zone'         => ['label' => 'Zone / Area',  'default' => true],
        'full_address' => ['label' => 'Full Address',  'default' => true],
        'year_stay'    => ['label' => 'Year of Stay',  'default' => false],
        'owner_status' => ['label' => 'Owner Status',  'default' => false],
    ],
    'Contact' => [
        'contact' => ['label' => 'Contact No', 'default' => true],
        'email'   => ['label' => 'Email',      'default' => false],
    ],
    'Status & Flags' => [
        'approval_status' => ['label' => 'Approval Status', 'default' => true],
        'is_active'       => ['label' => 'Active',          'default' => true],
        'is_pwd'          => ['label' => 'PWD',             'default' => false],
        'is_soloparents'  => ['label' => 'Solo Parent',     'default' => false],
        'voters'          => ['label' => 'Voter',           'default' => false],
        'pricinct_no'     => ['label' => 'Precinct No',     'default' => false],
        'is_id_release'   => ['label' => 'ID Released',     'default' => false],
        'is_verify'       => ['label' => 'Verified',        'default' => false],
    ],
    'Emergency Contact' => [
        'ic_fullname'     => ['label' => 'ICE Full Name',    'default' => false],
        'ic_relationship' => ['label' => 'ICE Relationship', 'default' => false],
        'ic_contact'      => ['label' => 'ICE Contact',      'default' => false],
        'ic_email'        => ['label' => 'ICE Email',        'default' => false],
        'ic_address'      => ['label' => 'ICE Address',      'default' => false],
    ],
    'Other' => [
        'note'              => ['label' => 'Notes',              'default' => false],
        'date_created'      => ['label' => 'Date Created',       'default' => false],
        'date_last_updated' => ['label' => 'Date Last Updated',  'default' => false],
    ],
];
@endphp

@can('citizens.export')
<div id="export-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    {{-- Backdrop --}}
    <div id="export-backdrop" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

    {{-- Dialog --}}
    <div class="relative flex items-center justify-center min-h-screen p-4 pointer-events-none">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl flex flex-col max-h-[90vh] pointer-events-auto">

            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 shrink-0">
                <div class="flex items-center gap-2">
                    <i class="mgc_file_download_line text-primary text-xl"></i>
                    <h5 class="font-semibold text-gray-800 dark:text-gray-100">Export Citizens</h5>
                    <span class="text-xs text-gray-400 ms-1">(<span id="export-match-count">{{ number_format($citizens->total()) }}</span> <span id="export-match-label">records match current filter</span>)</span>
                </div>
                <button type="button" id="close-export-modal"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <i class="mgc_close_line text-xl"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <form id="export-form" method="POST" action="{{ route('citizens.export') }}" class="flex flex-col overflow-hidden">
                @csrf

                {{-- Filled when the window opens: the list's current filters, or the ticked citizens --}}
                <div id="export-scope"></div>

                <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5">

                    {{-- Format --}}
                    <div>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Format</p>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="format" value="csv" checked
                                    class="accent-primary w-4 h-4">
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    <i class="mgc_table_2_line text-green-500 me-1"></i>CSV
                                    <span class="text-xs text-gray-400">(Excel-compatible, all records)</span>
                                </span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="format" value="pdf"
                                    class="accent-primary w-4 h-4">
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    <i class="mgc_pdf_line text-red-500 me-1"></i>PDF
                                    <span class="text-xs text-gray-400">(landscape A4)</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700">

                    {{-- Field Selection --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Fields to Export</p>
                            <div class="flex gap-2">
                                <button type="button" id="select-all-fields"
                                    class="text-xs text-primary hover:underline">Select All</button>
                                <span class="text-gray-300 dark:text-gray-600">|</span>
                                <button type="button" id="deselect-all-fields"
                                    class="text-xs text-gray-400 hover:underline">Clear All</button>
                            </div>
                        </div>

                        <div class="space-y-4">
                            @foreach($exportFieldGroups as $groupName => $groupFields)
                            <div class="export-group">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ $groupName }}
                                    </span>
                                    <button type="button"
                                        class="toggle-group text-xs text-gray-400 hover:text-primary hover:underline transition-colors"
                                        data-group="{{ $loop->index }}">
                                        Toggle All
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-1.5 bg-gray-50 dark:bg-gray-700/40 rounded-lg p-3"
                                     data-group-fields="{{ $loop->index }}">
                                    @foreach($groupFields as $fieldKey => $fieldMeta)
                                    <label class="flex items-center gap-2 cursor-pointer select-none">
                                        <input type="checkbox"
                                            name="fields[]"
                                            value="{{ $fieldKey }}"
                                            {{ $fieldMeta['default'] ? 'checked' : '' }}
                                            class="accent-primary w-3.5 h-3.5 export-field-checkbox rounded">
                                        <span class="text-xs text-gray-700 dark:text-gray-300">{{ $fieldMeta['label'] }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 shrink-0">
                    <p id="export-field-count" class="text-xs text-gray-400"></p>
                    <div class="flex gap-2">
                        <button type="button" id="cancel-export-modal"
                            class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                            Cancel
                        </button>
                        <button type="submit" id="export-submit-btn"
                            class="btn bg-primary text-white">
                            <i class="mgc_file_download_line me-1"></i>
                            <span id="export-submit-label">Export CSV</span>
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
@endcan

{{-- Citizen activity pop-up: opens when a Barangay ID is scanned anywhere on this page --}}
<div id="activity-modal" class="hidden fixed inset-0 z-50 bg-black/50 items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="act-name">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-3xl flex flex-col" style="max-height: 90vh;">
        {{-- Header --}}
        <div class="flex items-start gap-4 p-5 border-b dark:border-gray-700">
            <div id="act-avatar" class="w-16 h-16 rounded-full overflow-hidden bg-primary/10 text-primary flex items-center justify-center text-xl font-bold shrink-0"></div>
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 id="act-name" class="text-lg font-bold text-gray-800 dark:text-white"></h3>
                    <span id="act-code" class="text-sm text-gray-500 dark:text-gray-400"></span>
                </div>
                <div id="act-badges" class="flex flex-wrap gap-1.5 mt-1.5"></div>
                <p id="act-meta" class="text-xs text-gray-500 dark:text-gray-400 mt-2"></p>
            </div>
            <button type="button" onclick="closeCitizenActivity()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" aria-label="Close">
                <i class="mgc_close_line text-xl"></i>
            </button>
        </div>

        <div id="act-hold" class="hidden mx-5 mt-4 p-3 rounded-lg bg-danger/10 border border-danger/30 flex gap-2">
            <i class="mgc_lock_line text-danger text-lg shrink-0"></i>
            <p class="text-sm text-danger"><strong>On hold</strong> — cannot be issued documents. <span id="act-hold-reason"></span></p>
        </div>

        {{-- Category tabs --}}
        <div id="act-tabs" class="flex flex-wrap gap-2 px-5 pt-4"></div>

        {{-- Timeline --}}
        <div id="act-list" class="flex-1 overflow-y-auto px-5 py-3"></div>

        {{-- Footer --}}
        <div class="flex flex-wrap justify-end gap-2 py-3 px-5 border-t dark:border-gray-700">
            <button type="button" onclick="closeCitizenActivity()" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Close</button>
            <a id="act-request" href="#" class="hidden btn bg-success/25 text-success hover:bg-success hover:text-white"><i class="mgc_file_new_line"></i> New document request</a>
            <a id="act-profile" href="#" class="btn bg-primary text-white"><i class="mgc_user_3_line"></i> Open full profile</a>
        </div>
    </div>
</div>

@endsection

@section('script')
@php
    $allTagsJs = $allTags->pluck('name', 'id');
    $savedFiltersJs = $savedFilters->map(fn ($f) => [
        'id' => $f->id, 'name' => $f->name, 'query' => $f->query, 'is_shared' => $f->is_shared,
        'mine' => (int) $f->user_id === (int) auth()->id(), 'by' => $f->user->name ?? 'another user',
    ])->values();
@endphp
@include('partials.scan-anywhere')
<script>
// ── Citizen activity pop-up (opened by scanning an ID, or the history button on a row) ──
const ACTIVITY_URL = @json(route('citizens.activity'));
const ACT_CATS = {
    documents: { label: 'Documents', icon: 'mgc_file_line' },
    blotter:   { label: 'Blotter',   icon: 'mgc_alert_line' },
    events:    { label: 'Events',    icon: 'mgc_calendar_line' },
    health:    { label: 'Health',    icon: 'mgc_heartbeat_line' },
    inventory: { label: 'Inventory', icon: 'mgc_box_3_line' },
    records:   { label: 'Record',    icon: 'mgc_history_line' },
};
const ACT_TONES = {
    success: ['bg-green-100 text-green-800', 'bg-green-400'],
    warning: ['bg-yellow-100 text-yellow-800', 'bg-yellow-400'],
    danger:  ['bg-red-100 text-red-800', 'bg-red-400'],
    info:    ['bg-sky-100 text-sky-800', 'bg-sky-500'],
    gray:    ['bg-gray-100 text-gray-800', 'bg-gray-400'],
};
let actData = null, actTab = 'all', actSeq = 0;

const actEsc  = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const actPill = (text, tone) => {
    const [cls, dot] = ACT_TONES[tone] || ACT_TONES.gray;
    return `<span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium ${cls}"><span class="w-1.5 h-1.5 inline-block ${dot} rounded-full"></span>${actEsc(text)}</span>`;
};

function showActivityModal() {
    const m = document.getElementById('activity-modal');
    m.classList.remove('hidden');
    m.classList.add('flex');
}

function closeCitizenActivity() {
    const m = document.getElementById('activity-modal');
    m.classList.add('hidden');
    m.classList.remove('flex');
}

async function openCitizenActivity(params) {
    const mine = ++actSeq;                                  // only the latest scan may fill the pop-up
    showActivityModal();
    document.getElementById('act-name').textContent = 'Looking up…';
    ['act-code', 'act-meta', 'act-badges', 'act-tabs'].forEach(id => document.getElementById(id).innerHTML = '');
    document.getElementById('act-avatar').innerHTML = '<i class="mgc_loading_line animate-spin"></i>';
    document.getElementById('act-hold').classList.add('hidden');
    document.getElementById('act-request').classList.add('hidden');
    document.getElementById('act-profile').classList.add('hidden');
    document.getElementById('act-list').innerHTML = '<p class="text-center text-gray-400 py-10 text-sm">Loading activity…</p>';

    let res;
    try {
        const r = await fetch(ACTIVITY_URL + '?' + new URLSearchParams(params), { headers: { Accept: 'application/json' } });
        res = (r.ok || r.status === 404) ? await r.json()
            : { found: false, error: true, message: `The server could not load this citizen (error ${r.status}). Please scan again.` };
    } catch (e) {
        res = { found: false, error: true, message: 'Could not reach the server. Check the connection and scan again.' };
    }
    if (mine !== actSeq) return;                            // a newer scan came in while this one was loading

    if (!res.found) {
        document.getElementById('act-name').textContent = res.error ? 'Could not load' : 'Citizen not found';
        document.getElementById('act-avatar').innerHTML = '<i class="mgc_alert_line text-danger"></i>';
        document.getElementById('act-list').innerHTML =
            `<p class="text-center text-gray-500 dark:text-gray-400 py-10 text-sm">${actEsc(res.message || 'No citizen found for this code.')}${res.error ? '' : '<br>Check that the ID belongs to a registered citizen.'}</p>`;
        return;
    }

    actData = res;
    actTab  = 'all';
    const c = res.citizen;

    document.getElementById('act-avatar').innerHTML = c.photo
        ? `<img src="${actEsc(c.photo)}" alt="" class="w-full h-full object-cover">`
        : actEsc((c.name || '?').trim().split(/\s+/).map(w => w[0]).slice(0, 2).join('').toUpperCase());
    document.getElementById('act-name').textContent = c.name;
    document.getElementById('act-code').textContent = c.code;

    let badges = c.voter ? actPill('Voter', 'success') : actPill('Non-voter', 'gray');
    if (c.senior)   badges += actPill('Senior', 'warning');
    if (c.pwd)      badges += actPill('PWD', 'info');
    if (c.solo)     badges += actPill('Solo parent', 'info');
    if (c.archived) badges += actPill('Archived', 'gray');
    if (c.hold)     badges += actPill('On hold', 'danger');
    if (c.income)   badges += c.income.key ? actPill(c.income.label, c.income.tone) : actPill('Income not assessed', 'gray');
    (c.tags || []).forEach(t => { badges += `<span class="inline-flex items-center py-1 px-2.5 rounded-full text-xs font-medium text-white" style="background:${actEsc(t.color || '#6c757d')}">${actEsc(t.name)}</span>`; });
    document.getElementById('act-badges').innerHTML = badges;

    document.getElementById('act-meta').innerHTML = [
        [c.age != null ? `${c.age} yrs` : null, c.gender].filter(Boolean).join(' · '),
        c.address ? `<i class="mgc_location_line"></i> ${actEsc(c.address)}` : null,
        c.contact ? `<i class="mgc_phone_line"></i> ${actEsc(c.contact)}` : null,
        c.household ? `<i class="mgc_home_3_line"></i> ${actEsc(c.household)}` : null,
        c.id_valid ? `<i class="mgc_IDcard_line"></i> ID valid until ${actEsc(c.id_valid)}` : null,
    ].filter(Boolean).join('<span class="mx-1.5 text-gray-300">|</span>');

    if (c.hold) {
        document.getElementById('act-hold-reason').textContent = 'Reason: ' + c.hold;
        document.getElementById('act-hold').classList.remove('hidden');
    }

    const profile = document.getElementById('act-profile');
    profile.href = res.links.profile;
    profile.classList.remove('hidden');
    const req = document.getElementById('act-request');
    if (res.links.request && !c.hold && !c.archived) {
        req.href = res.links.request;
        req.classList.remove('hidden');
    }

    renderActivityTabs();
    renderActivityList();
}

function renderActivityTabs() {
    const counts = actData.counts || {};
    const total  = actData.items.length;
    const tab = (key, label, n) => `<button type="button" onclick="setActivityTab('${key}')"
        class="btn btn-sm gap-1 ${actTab === key ? 'bg-primary text-white' : 'bg-dark/10 text-slate-700 dark:text-slate-300 hover:bg-dark/20'}">${label} <span class="opacity-75">(${n})</span></button>`;
    document.getElementById('act-tabs').innerHTML = tab('all', 'All', total) +
        Object.keys(ACT_CATS).filter(k => counts[k] > 0).map(k => tab(k, ACT_CATS[k].label, counts[k])).join('');
}

function setActivityTab(key) {
    actTab = key;
    renderActivityTabs();
    renderActivityList();
}

function renderActivityList() {
    const items = actData.items.filter(i => actTab === 'all' || i.cat === actTab);
    if (!items.length) {
        document.getElementById('act-list').innerHTML = `<div class="text-center py-10 text-gray-400">
            <i class="mgc_box_3_line text-4xl mb-2 block opacity-30"></i>No activity yet.</div>`;
        return;
    }
    document.getElementById('act-list').innerHTML = items.map(i => {
        const cat  = ACT_CATS[i.cat] || ACT_CATS.records;
        const tag  = i.url ? 'a' : 'div';
        const href = i.url ? ` href="${actEsc(i.url)}"` : '';
        return `<${tag}${href} class="flex items-start gap-3 py-3 border-b last:border-0 dark:border-gray-700 ${i.url ? 'hover:bg-gray-50 dark:hover:bg-gray-700/40 rounded-lg px-2 -mx-2' : ''}">
            <span class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0"><i class="${i.icon || cat.icon}"></i></span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">${actEsc(i.title)}</p>
                ${i.sub ? `<p class="text-xs text-gray-500 dark:text-gray-400 truncate">${actEsc(i.sub)}</p>` : ''}
                <p class="text-xs text-gray-400 mt-0.5">${actEsc(cat.label)}${i.date ? ' · ' + actEsc(i.date) : ''}</p>
            </div>
            ${i.label ? `<div class="shrink-0">${actPill(i.label, i.tone)}</div>` : ''}
            ${i.url ? '<i class="mgc_right_line text-gray-300 shrink-0 mt-2"></i>' : ''}
        </${tag}>`;
    }).join('');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !document.getElementById('activity-modal').classList.contains('hidden')) closeCitizenActivity();
});
document.getElementById('activity-modal').addEventListener('click', e => {
    if (e.target.id === 'activity-modal') closeCitizenActivity();      // click outside the panel
});

// Scan a Barangay ID anywhere on this page → open that citizen's activity.
listenForScans({ onScan: code => openCitizenActivity({ code }) });
</script>
{{-- noUiSlider powers the Age Range control. Loaded here rather than through
     the Vite bundle because it is the only page that needs it, and this
     section renders before the page script below runs. --}}
<script src="https://cdn.jsdelivr.net/npm/nouislider@15.7.1/dist/nouislider.min.js"></script>
<script>
window.addEventListener('DOMContentLoaded', () => {

    // ── Filter toggle ──────────────────────────────────────
    document.getElementById('filter-toggle').addEventListener('click', () => {
        document.getElementById('filter-body').classList.toggle('hidden');
        document.getElementById('filter-chevron').classList.toggle('rotate-180');
        if (window.renderFilterChips) renderFilterChips();
    });
    // Close the panel once filters are applied so the results are in view.
    // The badge on the Advanced Filter button still shows how many are active.
    window.collapseFilterPanel = () => {
        document.getElementById('filter-body').classList.add('hidden');
        document.getElementById('filter-chevron').classList.remove('rotate-180');
        if (window.renderFilterChips) renderFilterChips();
    };

    // ── Age range slider ───────────────────────────────────
    // Two handles over 0–100. The hidden age_min / age_max inputs are what the
    // form submits, so the server contract is unchanged. An untouched slider
    // leaves them EMPTY rather than "0" and "100", so a full-width range is not
    // counted as an active filter.
    const AGE_FLOOR = 0, AGE_CEIL = 100;
    const ageSlider = document.getElementById('age-slider');
    const ageMinInp = document.getElementById('age-min');
    const ageMaxInp = document.getElementById('age-max');
    const ageLabel  = document.getElementById('age-range-label');

    const ageFrom = document.getElementById('age-from');
    const ageTo   = document.getElementById('age-to');

    function writeAgeInputs(lo, hi) {
        // Only record a bound when it is actually narrower than the full range.
        ageMinInp.value = lo > AGE_FLOOR ? lo : '';
        ageMaxInp.value = hi < AGE_CEIL  ? hi : '';
        // Mirror into the From / To boxes unless the user is typing in them.
        if (document.activeElement !== ageFrom) ageFrom.value = ageMinInp.value;
        if (document.activeElement !== ageTo)   ageTo.value   = ageMaxInp.value;
        ageLabel.textContent =
            (lo === AGE_FLOOR && hi === AGE_CEIL) ? 'All ages'
            : (hi === AGE_CEIL) ? lo + '+ yrs'
            : (lo === AGE_FLOOR) ? 'Under ' + (hi + 1) + ' yrs'
            : lo + '–' + hi + ' yrs';
    }

    // Set the range from numbers ('' = open end); drives the slider when it loaded.
    function setAgeRange(lo, hi) {
        lo = (lo === '' || lo === null || isNaN(parseInt(lo, 10))) ? AGE_FLOOR : Math.max(AGE_FLOOR, parseInt(lo, 10));
        hi = (hi === '' || hi === null || isNaN(parseInt(hi, 10))) ? AGE_CEIL  : Math.min(AGE_CEIL,  parseInt(hi, 10));
        if (hi < lo) [lo, hi] = [hi, lo];
        if (ageSlider && ageSlider.noUiSlider) {
            ageSlider.noUiSlider.set([lo, hi]);
        } else {
            writeAgeInputs(lo, hi);
        }
        if (typeof updateActiveCount === 'function') updateActiveCount();
    }
    window.setAgeRange = setAgeRange;

    [ageFrom, ageTo].forEach(inp => inp.addEventListener('change', () => setAgeRange(ageFrom.value, ageTo.value)));
    // Enter in an age box applies the filter like the other fields.
    [ageFrom, ageTo].forEach(inp => inp.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); setAgeRange(ageFrom.value, ageTo.value); }
        // The form's own Enter handler then applies the filter.
    }));

    function resetAgeSlider() {
        if (ageSlider && ageSlider.noUiSlider) {
            ageSlider.noUiSlider.set([AGE_FLOOR, AGE_CEIL]);
        } else {
            ageMinInp.value = ageMaxInp.value = '';
            ageFrom.value = ageTo.value = '';
            if (ageLabel) ageLabel.textContent = 'All ages';
        }
    }
    window.resetAgeSlider = resetAgeSlider;

    function initAgeSlider() {
        if (!ageSlider || typeof noUiSlider === 'undefined') {
            // Slider library unavailable (offline): the From / To boxes still work.
            if (ageSlider) ageSlider.parentElement.classList.add('hidden');
            writeAgeInputs(
                ageMinInp.value === '' ? AGE_FLOOR : parseInt(ageMinInp.value, 10),
                ageMaxInp.value === '' ? AGE_CEIL  : parseInt(ageMaxInp.value, 10));
            return;
        }

        const startLo = ageMinInp.value === '' ? AGE_FLOOR : Math.max(AGE_FLOOR, parseInt(ageMinInp.value, 10));
        const startHi = ageMaxInp.value === '' ? AGE_CEIL  : Math.min(AGE_CEIL,  parseInt(ageMaxInp.value, 10));

        noUiSlider.create(ageSlider, {
            start: [startLo, startHi],
            connect: true,
            step: 1,
            range: { min: AGE_FLOOR, max: AGE_CEIL },
            // Keep the handles from crossing over each other.
            margin: 0,
            format: {
                to:   v => Math.round(v),
                from: v => Number(v),
            },
        });

        // 'update' fires while dragging — cheap, so the label tracks live.
        ageSlider.noUiSlider.on('update', (values) => {
            writeAgeInputs(Number(values[0]), Number(values[1]));
        });
        // Only re-query once the handle is released, not on every pixel.
        ageSlider.noUiSlider.on('change', () => {
            if (typeof updateActiveCount === 'function') updateActiveCount();
        });

        writeAgeInputs(startLo, startHi);
    }

    initAgeSlider();

    // ── Row selection → floating action bar ───────────────
    // The table is wider than the viewport, so the Action column can sit
    // off-screen. Selecting a row brings its actions to a docked bar that is
    // always visible.
    const actionBar = document.getElementById('action-bar');
    let selectedRow = null;

    function hideActionBar() {
        actionBar.classList.add('translate-y-full', 'opacity-0', 'pointer-events-none');
        document.querySelectorAll('.row-selected').forEach(r => r.classList.remove('row-selected'));
        selectedRow = null;
    }

    function showActionBar(row) {
        const { id, name, code, photo } = row.dataset;

        document.querySelectorAll('.row-selected').forEach(r => r.classList.remove('row-selected'));
        row.classList.add('row-selected');
        selectedRow = row;

        document.getElementById('ab-name').textContent = name;
        document.getElementById('ab-code').textContent = code;

        const avatar = document.getElementById('ab-avatar');
        avatar.innerHTML = photo
            ? `<img src="${photo}" class="w-full h-full object-cover">`
            : name.replace(',', '').trim().split(/\s+/).slice(0, 2).map(w => w[0] || '').join('').toUpperCase();

        document.getElementById('ab-view').href = URLS.citizens + '/' + id;
        // Buttons the role can't use aren't rendered, so set links only on those that exist.
        const setHref = (elId, href) => { const el = document.getElementById(elId); if (el) el.href = href; };
        setHref('ab-edit', URLS.citizens + '/' + id + '/edit');
        setHref('ab-id',   URLS.citizenIds + '?search=' + encodeURIComponent(name.split(',')[0]));
        setHref('ab-doc',  URLS.newDocument + '?citizen=' + id);

        // Absent for users without citizens.archive.
        const del = document.getElementById('ab-delete');
        if (del) {
            del.dataset.id = id;
            del.dataset.name = name;
        }

        actionBar.classList.remove('translate-y-full', 'opacity-0', 'pointer-events-none');
    }

    // Delegated so it survives the AJAX table swaps.
    document.addEventListener('click', e => {
        // Let the real controls in the sticky Action column do their job.
        if (e.target.closest('a, button, input, select, label')) return;

        // Table rows and grid cards both carry .citizen-row.
        const row = e.target.closest('.citizen-row');
        if (!row) return;

        // Clicking the selected row again clears it.
        if (row === selectedRow) { hideActionBar(); return; }
        showActionBar(row);
    });

    document.getElementById('ab-close').addEventListener('click', hideActionBar);
    document.getElementById('ab-activity').addEventListener('click', () => {
        if (selectedRow) openCitizenActivity({ id: selectedRow.dataset.id });
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') hideActionBar(); });

    // Built server-side so they work under a subdirectory install.
    const URLS = {
        citizens:    @json(url('citizens')),
        citizenIds:  @json(url('citizens/ids')),
        newDocument: @json(url('documents/requests/create')),
    };

    // ── Set inactive (archive) confirmation ────────────────
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-delete');
        if (!btn) return;
        const id   = btn.dataset.id;
        const name = btn.dataset.name;

        Swal.fire({
            title: 'Set as Inactive?',
            html: `<p class="text-sm">"<b>${name}</b>" will be moved to the Archive.
                   You can restore them later.</p>`,
            icon: 'warning',
            input: 'textarea',
            inputLabel: 'Reason (required)',
            inputPlaceholder: 'e.g. Moved out of barangay, deceased, duplicate record…',
            showCancelButton: true,
            confirmButtonText: 'Yes, set inactive',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            inputValidator: value => {
                if (!value || value.trim().length < 5) {
                    return 'Please give a reason of at least 5 characters.';
                }
            },
            didOpen: () => {
                // Amber, not red — this archives rather than destroys.
                document.querySelector('.swal2-confirm').style.setProperty('background-color', '#f5a623', 'important');
            },
        }).then(result => {
            if (!result.isConfirmed) return;
            document.getElementById('archive-reason').value = result.value.trim();
            document.getElementById('delete-form').setAttribute('action', URLS.citizens + '/' + id);
            document.getElementById('delete-form').submit();
        });
    });

    // ── AJAX filter engine ────────────────────────────────
    const wrap      = document.getElementById('citizens-table-wrap');
    const overlay   = document.getElementById('table-loading');
    const form      = document.getElementById('filter-form');
    const perPage   = document.getElementById('per-page-select');
    const applyBtn  = document.getElementById('apply-filters-btn');
    const clearBtn  = document.getElementById('clear-filters-btn');
    const AJAX_URL  = '{{ route('citizens.index') }}';

    let currentPage = 1;

    // Sort state lives outside the filter form, so carry it separately.
    const initialParams = new URLSearchParams(window.location.search);

    // Table / grid view. The URL wins (shared links), then the last choice
    // made in this browser, then table.
    const VIEW_KEY = 'citizens.view';
    const renderedView = initialParams.get('view') === 'grid' ? 'grid' : 'table';
    let currentView = renderedView;
    if (!initialParams.has('view')) {
        try { if (localStorage.getItem(VIEW_KEY) === 'grid') currentView = 'grid'; } catch (e) {}
    }
    let currentSort = initialParams.get('sort') || '';
    let currentDir  = initialParams.get('dir')  || 'desc';

    // Voters defaults to "Voter" without the user asking, so track whether
    // they have actually chosen a value before counting it as a filter.
    let votersTouched = initialParams.has('voters');
    const votersSelect = form.querySelector('[name="voters"]');
    if (votersSelect) {
        votersSelect.addEventListener('change', () => {
            votersTouched = true;
            updateActiveCount();
        });
    }

    function getParams() {
        const data = new FormData(form);
        const params = new URLSearchParams();
        for (const [k, v] of data.entries()) {
            // append keeps every ticked tag. An empty "voters" (= All) is sent
            // on purpose: without it the server falls back to voters only.
            if (v !== '' || k === 'voters') params.append(k, v);
        }
        params.set('per_page', perPage.value);
        params.set('page', currentPage);
        if (currentView === 'grid') params.set('view', 'grid');
        if (currentSort) {
            params.set('sort', currentSort);
            params.set('dir', currentDir);
        }
        return params;
    }

    function updateActiveCount() {
        const data = new FormData(form);
        let count = 0;
        for (const [k, v] of data.entries()) {
            if (v === '') continue;
            // Search has its own always-visible box outside this panel.
            if (k === 'search') continue;
            // Voters is pre-selected by default; it only counts as an active
            // filter once the user picks something other than that default.
            if (k === 'voters' && v === '1' && !votersTouched) continue;
            count++;
        }
        const badge = document.getElementById('filter-active-badge');
        if (badge) {
            badge.textContent = count;
            badge.classList.toggle('hidden', count === 0);
        }
    }

    // ── "Filtered by" chips ───────────────────────────────
    // While the panel is closed, list every filter in plain words so staff
    // can see why the list looks the way it does. Each chip's × removes
    // just that filter.
    const chipBar = document.getElementById('filter-chips');
    // Selects whose option text already says it all ("PWD", "Non-Voter").
    const SELF_LABELLED = ['voters', 'is_pwd', 'is_soloparents', 'senior'];

    function fieldLabel(el) {
        const lbl = el.parentElement.querySelector('label');
        return lbl ? lbl.textContent.trim() : el.name;
    }

    let resultTotal = window.citizenListTotal = {{ (int) $citizens->total() }};
    const exportCount = document.getElementById('export-match-count');

    window.renderFilterChips = function () {
        if (!chipBar) return;
        const chips = [];
        const add = (text, remove) => chips.push({ text, remove });

        if (searchField.value.trim()) {
            add('Search: “' + searchField.value.trim() + '”', () => { searchField.value = ''; quickSearch.value = ''; });
        }
        form.querySelectorAll('select').forEach(sel => {
            if (sel.value === '') return;
            const opt = sel.options[sel.selectedIndex].textContent.trim();
            let text = SELF_LABELLED.includes(sel.name) ? opt : fieldLabel(sel) + ': ' + opt;
            if (sel.name === 'voters') text = (sel.value === '1' ? 'Voters only' : 'Non-voters only') + (votersTouched ? '' : ' (default)');
            add(text, () => {
                sel.value = '';
                if (sel.name === 'voters') votersTouched = true;
            });
        });
        const lo = document.getElementById('age-min').value, hi = document.getElementById('age-max').value;
        if (lo !== '' || hi !== '') {
            const age = lo !== '' && hi !== '' ? (lo === hi ? lo : lo + '–' + hi)
                      : lo !== '' ? lo + ' and up' : 'up to ' + hi;
            add('Age: ' + age, () => resetAgeSlider());
        }
        const hh = form.querySelector('[name="household_search"]');
        if (hh && hh.value.trim()) add('Household: ' + hh.value.trim(), () => { hh.value = ''; });
        form.querySelectorAll('input[name="tags[]"]:checked').forEach(cb => {
            const name = cb.closest('label')?.querySelector('span')?.textContent.trim() || 'Tag';
            add('Tag: ' + name, () => { cb.checked = false; });
        });

        const panelOpen = !document.getElementById('filter-body').classList.contains('hidden');
        chipBar.replaceChildren();
        chipBar.classList.toggle('hidden', panelOpen || chips.length === 0);
        if (panelOpen || chips.length === 0) return;

        const lead = document.createElement('span');
        lead.className = 'text-xs font-medium text-gray-500 dark:text-gray-400 flex items-center gap-1';
        lead.innerHTML = '<i class="mgc_filter_line"></i> Filtered by:';
        chipBar.append(lead);

        chips.forEach(c => {
            const chip = document.createElement('span');
            chip.className = 'inline-flex items-center gap-1 py-1 ps-3 pe-1 rounded-full text-xs font-medium bg-primary/10 text-primary';
            chip.append(c.text);
            const x = document.createElement('button');
            x.type = 'button';
            x.className = 'w-5 h-5 rounded-full flex items-center justify-center hover:bg-primary hover:text-white';
            x.title = 'Remove this filter';
            x.innerHTML = '<i class="mgc_close_line text-xs"></i>';
            x.addEventListener('click', () => { c.remove(); updateActiveCount(); renderFilterChips(); fetchTable(true); });
            chip.append(x);
            chipBar.append(chip);
        });

        if (chips.length > 1) {
            const all = document.createElement('button');
            all.type = 'button';
            all.className = 'text-xs font-medium text-danger hover:underline ms-1';
            all.textContent = 'Clear all';
            all.addEventListener('click', () => clearBtn.click());
            chipBar.append(all);
        }

        // How many citizens these filters leave.
        const count = document.createElement('span');
        count.className = 'ms-auto inline-flex items-center gap-1.5 py-1 px-3 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200';
        count.innerHTML = '<i class="mgc_user_3_line"></i>';
        count.append(resultTotal.toLocaleString() + (resultTotal === 1 ? ' citizen found' : ' citizens found'));
        chipBar.append(count);
    };


    // Only the newest request may update the table. While typing, several
    // searches can be in flight; without this a slow, older response could
    // land last and overwrite the results for what is actually in the box.
    let inflight = null;

    function fetchTable(resetPage) {
        if (resetPage) currentPage = 1;
        overlay.classList.remove('hidden');

        const params = getParams();
        history.replaceState(null, '', AJAX_URL + '?' + params.toString());

        if (inflight) inflight.abort();
        const controller = new AbortController();
        inflight = controller;

        fetch(AJAX_URL + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal,
        })
        .then(r => r.json())
        .then(res => {
            wrap.innerHTML = res.html;
            overlay.classList.add('hidden');
            if (res.stats) paintStatCounts(res.stats);
            if (typeof res.total === 'number') {
                resultTotal = window.citizenListTotal = res.total;
                if (exportCount) exportCount.textContent = res.total.toLocaleString();
            }
            updateActiveCount();
            renderFilterChips();
            paintStatCards();
            syncChecks();
            // Intercept pagination + sort links in the swapped HTML
            attachPaginationLinks();
            attachSortLinks();
        })
        .catch(err => {
            // A superseded request is aborted on purpose — the newer one
            // owns the overlay, so leave it alone.
            if (err.name !== 'AbortError') overlay.classList.add('hidden');
        })
        .finally(() => { if (inflight === controller) inflight = null; });
    }

    function attachPaginationLinks() {
        // Sort links also carry page=1, so skip them here — they have their
        // own handler that preserves the chosen column and direction.
        wrap.querySelectorAll('a[href*="page="]:not([data-sort])').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                currentPage = parseInt(new URL(a.href).searchParams.get('page')) || 1;
                fetchTable(false);
            });
        });
    }

    function attachSortLinks() {
        wrap.querySelectorAll('a[data-sort]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const url = new URL(a.href);
                currentSort = url.searchParams.get('sort') || '';
                currentDir  = url.searchParams.get('dir')  || 'desc';
                fetchTable(true);
            });
        });
    }

    // ── Quick search (always visible, outside the collapsible panel) ──
    // It mirrors into the form's hidden search field so getParams() — which
    // reads the form — picks it up like any other filter.
    const quickSearch = document.getElementById('quick-search');
    const searchField = document.getElementById('search-field');

    function runQuickSearch() {
        clearTimeout(quickTimer);
        searchField.value = quickSearch.value.trim();
        fetchTable(true);
    }

    document.getElementById('quick-search-btn').addEventListener('click', runQuickSearch);

    quickSearch.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); runQuickSearch(); }
    });

    // Live search as the user types. A short pause avoids a request per
    // keystroke; typing that does not change the term (e.g. a trailing
    // space) does not re-query.
    let quickTimer = null;
    quickSearch.addEventListener('input', () => {
        clearTimeout(quickTimer);
        if (quickSearch.value.trim() === searchField.value) return;
        quickTimer = setTimeout(runQuickSearch, 250);
    });

    // Apply button
    applyBtn.addEventListener('click', () => { fetchTable(true); collapseFilterPanel(); });

    // Also allow Enter key in any filter input to trigger apply
    form.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); fetchTable(true); collapseFilterPanel(); }
    });

    // Per-page fires immediately (no ambiguity)
    perPage.addEventListener('change', () => fetchTable(true));

    // Clear — reset all fields then fetch
    clearBtn.addEventListener('click', () => {
        form.querySelectorAll('input[type="text"], input[type="number"]').forEach(i => i.value = '');
        form.querySelectorAll('select').forEach(s => s.value = '');
        form.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = false);
        resetAgeSlider();   // hidden inputs are not covered by the selectors above
        // The search term lives in a hidden field mirrored from the always
        // visible box, so clear both.
        searchField.value = '';
        quickSearch.value = '';
        fetchTable(true);
    });

    // Initial server-rendered pagination + sort links
    attachPaginationLinks();
    attachSortLinks();
    renderFilterChips();

    // ── View toggle ─────────────────────────────────────────────
    const viewBtns = document.querySelectorAll('.view-toggle');
    function paintViewToggle() {
        viewBtns.forEach(b => {
            const on = b.dataset.view === currentView;
            b.classList.toggle('bg-primary', on);
            b.classList.toggle('text-white', on);
            b.classList.toggle('hover:text-primary', !on);
            b.classList.toggle('text-gray-500', !on);
            b.classList.toggle('dark:text-gray-400', !on);
        });
    }
    viewBtns.forEach(b => b.addEventListener('click', () => {
        if (b.dataset.view === currentView) return;
        currentView = b.dataset.view;
        try { localStorage.setItem(VIEW_KEY, currentView); } catch (e) {}
        paintViewToggle();
        if (typeof hideActionBar === 'function') hideActionBar();
        fetchTable(false);   // keep the current page
    }));
    paintViewToggle();
    // Remembered grid choice but the server rendered the table: swap once.
    if (currentView !== renderedView) fetchTable(false);

    // Keep overlay inside wrap after every innerHTML swap
    const observer = new MutationObserver(() => {
        if (!wrap.contains(overlay)) wrap.prepend(overlay);
    });
    observer.observe(wrap, { childList: true });

    // ── Shared helpers ─────────────────────────────────────
    const CSRF = @json(csrf_token());
    const swalColor = color => () => {
        const b = document.querySelector('.swal2-confirm');
        if (b) b.style.setProperty('background-color', color, 'important');
    };
    const toast = (icon, title) => Swal.fire({ toast: true, position: 'top-end', icon, title, timer: 2600, showConfirmButton: false });
    async function postJson(url, body, method = 'POST') {
        const r = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: body ? JSON.stringify(body) : null,
        });
        const data = await r.json().catch(() => ({}));
        if (!r.ok) {
            const first = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(first || data.message || ('The server said no (error ' + r.status + ').'));
        }
        return data;
    }
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    // ── Quick-filter count cards ───────────────────────────
    // Each card shows one group. It replaces the group filters (voter, senior,
    // PWD, solo parent, age) and keeps the rest (zone, search, tags…).
    const GROUP_SELECTS = ['voters', 'senior', 'is_pwd', 'is_soloparents'];
    const QUICK = {
        all:     {},
        voters:  { voters: '1' },
        seniors: { senior: '1' },
        pwd:     { is_pwd: '1' },
        solo:    { is_soloparents: '1' },
        minors:  { age: [0, 17] },
    };
    const sel = name => form.querySelector('[name="' + name + '"]');

    function currentQuick() {
        const state = {};
        GROUP_SELECTS.forEach(n => { const el = sel(n); if (el && el.value !== '') state[n] = el.value; });
        const lo = document.getElementById('age-min').value, hi = document.getElementById('age-max').value;
        if (lo !== '' || hi !== '') state.age = [lo === '' ? 0 : +lo, hi === '' ? 100 : +hi];
        return Object.keys(QUICK).find(k => {
            const want = QUICK[k];
            const keys = new Set([...Object.keys(want), ...Object.keys(state)]);
            return [...keys].every(key => JSON.stringify(want[key]) === JSON.stringify(state[key]));
        }) || null;
    }

    // Card numbers follow the list's other filters; refreshed with every list load.
    const STAT_FIELD = { all: 'total', voters: 'voters', seniors: 'seniors', pwd: 'pwd', solo: 'solo', minors: 'minors' };
    function paintStatCounts(stats) {
        document.querySelectorAll('[data-stat]').forEach(el => {
            const n = stats[STAT_FIELD[el.dataset.stat]];
            if (typeof n === 'number') el.textContent = n.toLocaleString();
        });
    }

    function paintStatCards() {
        const on = currentQuick();
        document.querySelectorAll('.stat-card').forEach(c => {
            const active = c.dataset.quick === on;
            c.classList.toggle('border-primary', active);
            c.classList.toggle('border-transparent', !active);
            c.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    document.querySelectorAll('.stat-card').forEach(card => card.addEventListener('click', () => {
        const want = QUICK[card.dataset.quick];
        GROUP_SELECTS.forEach(n => { const el = sel(n); if (el) el.value = want[n] ?? ''; });
        votersTouched = true;
        if (want.age) setAgeRange(want.age[0], want.age[1]); else resetAgeSlider();
        updateActiveCount();
        fetchTable(true);
        paintStatCards();
    }));

    // ── Bulk selection ─────────────────────────────────────
    // Ticks are kept by citizen id, so they survive paging, sorting and filtering.
    const selected = new Set();
    const bulkBar = document.getElementById('bulk-bar');

    function syncChecks() {
        const boxes = [...wrap.querySelectorAll('.bulk-check')];
        boxes.forEach(cb => { cb.checked = selected.has(cb.value); });
        const ids = [...new Set(boxes.map(cb => cb.value))];
        const onPage = ids.filter(id => selected.has(id)).length;
        wrap.querySelectorAll('.bulk-check-page').forEach(pc => {
            pc.checked = ids.length > 0 && onPage === ids.length;
            pc.indeterminate = onPage > 0 && onPage < ids.length;
        });
        document.getElementById('bulk-count').textContent = selected.size.toLocaleString();
        bulkBar.classList.toggle('hidden', selected.size === 0);
    }

    wrap.addEventListener('change', e => {
        if (e.target.classList.contains('bulk-check')) {
            e.target.checked ? selected.add(e.target.value) : selected.delete(e.target.value);
            syncChecks();
        } else if (e.target.classList.contains('bulk-check-page')) {
            const on = e.target.checked;
            wrap.querySelectorAll('.bulk-check').forEach(cb => on ? selected.add(cb.value) : selected.delete(cb.value));
            syncChecks();
        }
    });

    function clearSelection() { selected.clear(); syncChecks(); }
    document.getElementById('bulk-clear').addEventListener('click', clearSelection);

    const BULK_URL = @json(route('citizens.bulk'));
    const ALL_TAGS = @json($allTagsJs);
    const plural = n => n.toLocaleString() + (n === 1 ? ' citizen' : ' citizens');

    function tooMany() {
        if (selected.size <= 500) return false;
        Swal.fire({ icon: 'info', title: 'Too many selected', text: 'Bulk changes work on up to 500 citizens at a time. You have ' + selected.size.toLocaleString() + ' ticked.', didOpen: swalColor('#727cf5') });
        return true;
    }

    async function runBulk(payload, after) {
        try {
            const res = await postJson(BULK_URL, { ...payload, ids: [...selected].map(Number) });
            toast('success', res.message);
            after && after(res);
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Could not finish', text: err.message, didOpen: swalColor('#727cf5') });
        }
    }

    function bulkTag(action) {
        if (tooMany()) return;
        const adding = action === 'tag_add';
        Swal.fire({
            title: adding ? 'Add a tag' : 'Remove a tag',
            text: (adding ? 'Add this tag to ' : 'Remove this tag from ') + plural(selected.size) + '.',
            input: 'select',
            inputOptions: ALL_TAGS,
            inputPlaceholder: 'Choose a tag',
            showCancelButton: true,
            confirmButtonText: adding ? 'Add tag' : 'Remove tag',
            reverseButtons: true,
            inputValidator: v => !v && 'Choose a tag.',
            didOpen: swalColor(adding ? '#727cf5' : '#fa5c7c'),
        }).then(r => {
            if (r.isConfirmed) runBulk({ action, tag_id: Number(r.value) }, () => fetchTable(false));
        });
    }
    document.getElementById('bulk-tag-add')?.addEventListener('click', () => bulkTag('tag_add'));
    document.getElementById('bulk-tag-remove')?.addEventListener('click', () => bulkTag('tag_remove'));

    document.getElementById('bulk-export')?.addEventListener('click', () => {
        if (window.openExportModal) window.openExportModal([...selected]);
    });

    document.getElementById('bulk-archive')?.addEventListener('click', () => {
        if (tooMany()) return;
        Swal.fire({
            title: 'Set ' + plural(selected.size) + ' as inactive?',
            html: '<p class="text-sm">They will be moved to the Archive. You can restore them later.</p>',
            icon: 'warning',
            input: 'textarea',
            inputLabel: 'Reason (required)',
            inputPlaceholder: 'e.g. Moved out of barangay, duplicate records…',
            showCancelButton: true,
            confirmButtonText: 'Yes, set inactive',
            reverseButtons: true,
            inputValidator: v => (!v || v.trim().length < 5) && 'Please give a reason of at least 5 characters.',
            didOpen: swalColor('#f5a623'),
        }).then(r => {
            // Reload so the count cards and totals reflect the archived records.
            if (r.isConfirmed) runBulk({ action: 'archive', reason: r.value.trim() }, () => { selected.clear(); setTimeout(() => location.reload(), 900); });
        });
    });

    // ── Column chooser ─────────────────────────────────────
    const COL_KEY = 'citizens.columns.' + @json(auth()->id());
    const colStyle = document.createElement('style');
    document.head.append(colStyle);
    let hiddenCols = [];
    try { hiddenCols = JSON.parse(localStorage.getItem(COL_KEY) || '[]'); } catch (e) { hiddenCols = []; }
    if (!Array.isArray(hiddenCols)) hiddenCols = [];

    function paintColumns() {
        colStyle.textContent = hiddenCols.map(c => '#citizens-table-wrap [data-col="' + c.replace(/[^a-z]/g, '') + '"]{display:none}').join('\n');
        document.querySelectorAll('.col-toggle').forEach(cb => { cb.checked = !hiddenCols.includes(cb.value); });
        try { localStorage.setItem(COL_KEY, JSON.stringify(hiddenCols)); } catch (e) {}
    }
    document.querySelectorAll('.col-toggle').forEach(cb => cb.addEventListener('change', () => {
        hiddenCols = [...document.querySelectorAll('.col-toggle')].filter(x => !x.checked).map(x => x.value);
        paintColumns();
    }));
    document.getElementById('col-reset').addEventListener('click', () => { hiddenCols = []; paintColumns(); });
    paintColumns();

    // Small drop-down menus (Columns, Saved filters): toggle on the button, close on outside click.
    function dropdown(btnId, menuId) {
        const btn = document.getElementById(btnId), menu = document.getElementById(menuId);
        if (!btn || !menu) return;
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const open = menu.classList.contains('hidden');
            document.querySelectorAll('#col-chooser-menu, #saved-filters-menu').forEach(m => m.classList.add('hidden'));
            menu.classList.toggle('hidden', !open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        menu.addEventListener('click', e => e.stopPropagation());
        document.addEventListener('click', () => { menu.classList.add('hidden'); btn.setAttribute('aria-expanded', 'false'); });
    }
    dropdown('col-chooser-btn', 'col-chooser-menu');
    dropdown('saved-filters-btn', 'saved-filters-menu');

    // The chooser only affects the table, so hide it in grid view.
    const colChooser = document.getElementById('col-chooser');
    function paintColChooser() { colChooser.style.display = currentView === 'grid' ? 'none' : ''; }
    viewBtns.forEach(b => b.addEventListener('click', paintColChooser));
    paintColChooser();

    // ── Saved filters ──────────────────────────────────────
    const SAVED_URL = @json(route('citizens.saved-filters.store'));
    let savedFilters = @json($savedFiltersJs);
    const savedList = document.getElementById('saved-filters-list');

    function renderSaved() {
        if (!savedFilters.length) {
            savedList.innerHTML = '<p class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">No saved filters yet. Set up the list the way you like, then save it here.</p>';
            return;
        }
        savedList.innerHTML = savedFilters.map(f => `
            <div class="group flex items-center gap-1 px-2">
                <a href="${AJAX_URL}?${esc(f.query)}" class="flex-1 min-w-0 px-2 py-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <span class="block text-sm text-gray-800 dark:text-gray-100 truncate">${esc(f.name)}</span>
                    ${f.mine ? (f.is_shared ? '<span class="block text-[11px] text-info">Shared with all staff</span>' : '')
                             : `<span class="block text-[11px] text-gray-400">Shared by ${esc(f.by)}</span>`}
                </a>
                ${f.mine ? `<button type="button" data-del="${f.id}" class="p-1.5 rounded text-gray-400 hover:text-danger hover:bg-danger/10" title="Delete this saved filter"><i class="mgc_delete_2_line"></i></button>` : ''}
            </div>`).join('');
    }
    renderSaved();

    document.getElementById('save-filter-btn').addEventListener('click', () => {
        const params = getParams();
        params.delete('page');
        params.delete('view');
        Swal.fire({
            title: 'Save these filters',
            html: `<p class="text-sm text-gray-500 mb-3">Give this view a name, e.g. "Purok 1 · Seniors · Voters".</p>
                   <input id="sf-name" class="swal2-input" maxlength="80" placeholder="Name" style="margin:0 auto;width:100%">
                   <label class="flex items-center justify-center gap-2 mt-3 text-sm"><input type="checkbox" id="sf-shared"> Share with all staff</label>`,
            showCancelButton: true,
            confirmButtonText: 'Save',
            reverseButtons: true,
            focusConfirm: false,
            didOpen: () => { swalColor('#727cf5')(); document.getElementById('sf-name').focus(); },
            preConfirm: () => {
                const name = document.getElementById('sf-name').value.trim();
                if (!name) { Swal.showValidationMessage('Please enter a name.'); return false; }
                return { name, is_shared: document.getElementById('sf-shared').checked };
            },
        }).then(async r => {
            if (!r.isConfirmed) return;
            try {
                const res = await postJson(SAVED_URL, { ...r.value, query: params.toString() });
                savedFilters.unshift({ ...res.filter, mine: true });
                renderSaved();
                toast('success', 'Saved "' + res.filter.name + '"');
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Could not save', text: err.message, didOpen: swalColor('#727cf5') });
            }
        });
    });

    savedList.addEventListener('click', e => {
        const del = e.target.closest('[data-del]');
        if (!del) return;
        const f = savedFilters.find(x => String(x.id) === del.dataset.del);
        Swal.fire({
            title: 'Delete saved filter?',
            text: '"' + f.name + '" will be removed' + (f.is_shared ? ' for everyone it was shared with.' : '.'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            reverseButtons: true,
            didOpen: swalColor('#fa5c7c'),
        }).then(async r => {
            if (!r.isConfirmed) return;
            try {
                await postJson(SAVED_URL + '/' + f.id, null, 'DELETE');
                savedFilters = savedFilters.filter(x => x !== f);
                renderSaved();
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Could not delete', text: err.message, didOpen: swalColor('#727cf5') });
            }
        });
    });

    // ── Quick view panel ───────────────────────────────────
    const qv = document.getElementById('qv'), qvPanel = document.getElementById('qv-panel'), qvBody = document.getElementById('qv-body');
    let qvId = null, qvSeq = 0;

    function closeQuickView() {
        qvPanel.classList.add('translate-x-full');
        setTimeout(() => qv.classList.add('hidden'), 180);
        qvId = null;
    }

    async function openQuickView(id) {
        const mine = ++qvSeq;
        qvId = id;
        qv.classList.remove('hidden');
        requestAnimationFrame(() => qvPanel.classList.remove('translate-x-full'));
        qvBody.innerHTML = '<p class="text-center text-gray-400 py-16 text-sm"><i class="mgc_loading_line animate-spin"></i> Loading…</p>';
        document.getElementById('qv-profile').href = URLS.citizens + '/' + id;
        const edit = document.getElementById('qv-edit');
        if (edit) edit.href = URLS.citizens + '/' + id + '/edit';

        let res;
        try {
            const r = await fetch(ACTIVITY_URL + '?' + new URLSearchParams({ id }), { headers: { Accept: 'application/json' } });
            res = await r.json();
        } catch (e) {
            res = { found: false, message: 'Could not reach the server. Please try again.' };
        }
        if (mine !== qvSeq) return;
        if (!res.found) {
            qvBody.innerHTML = `<p class="text-center text-gray-500 dark:text-gray-400 py-16 text-sm">${esc(res.message || 'Citizen not found.')}</p>`;
            return;
        }

        const c = res.citizen;
        const initials = esc((c.name || '?').replace(',', '').trim().split(/\s+/).map(w => w[0]).slice(0, 2).join('').toUpperCase());
        let badges = c.voter ? actPill('Voter', 'success') : actPill('Non-voter', 'gray');
        if (c.senior) badges += actPill('Senior', 'warning');
        if (c.pwd)    badges += actPill('PWD', 'info');
        if (c.solo)   badges += actPill('Solo parent', 'info');
        if (c.hold)   badges += actPill('On hold', 'danger');
        if (c.income) badges += c.income.key ? actPill(c.income.label, c.income.tone) : actPill('Income not assessed', 'gray');
        (c.tags || []).forEach(t => { badges += `<span class="inline-flex items-center py-1 px-2.5 rounded-full text-xs font-medium text-white" style="background:${esc(t.color || '#6c757d')}">${esc(t.name)}</span>`; });

        const line = (icon, label, value) => value ? `
            <div class="flex gap-3 py-2">
                <i class="${icon} text-gray-400 text-lg shrink-0"></i>
                <div class="min-w-0"><p class="text-[11px] uppercase tracking-wide text-gray-400">${label}</p>
                <p class="text-sm text-gray-800 dark:text-gray-100 break-words">${esc(value)}</p></div>
            </div>` : '';

        const recent = (res.items || []).slice(0, 5);
        const recentHtml = recent.length ? recent.map(i => {
            const cat = ACT_CATS[i.cat] || ACT_CATS.records;
            return `<div class="flex items-start gap-3 py-2.5 border-b last:border-0 dark:border-gray-700">
                <span class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0"><i class="${esc(i.icon || cat.icon)}"></i></span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-800 dark:text-gray-100 truncate">${esc(i.title)}</p>
                    <p class="text-xs text-gray-400">${esc(cat.label)}${i.date ? ' · ' + esc(i.date) : ''}</p>
                </div>
                ${i.label ? `<div class="shrink-0">${actPill(i.label, i.tone)}</div>` : ''}
            </div>`;
        }).join('') : '<p class="text-sm text-gray-400 py-3">No activity yet.</p>';

        qvBody.innerHTML = `
            <div class="p-5 flex flex-col items-center text-center border-b dark:border-gray-700">
                <div class="w-24 h-24 rounded-full overflow-hidden bg-primary/10 text-primary flex items-center justify-center text-2xl font-bold ${c.hold ? 'ring-4 ring-danger/40' : ''}">
                    ${c.photo ? `<img src="${esc(c.photo)}" alt="" class="w-full h-full object-cover">` : initials}
                </div>
                <h4 id="qv-name" class="mt-3 text-lg font-bold text-gray-800 dark:text-white">${esc(c.name)}</h4>
                <p class="text-xs font-mono text-gray-400">${esc(c.code)}</p>
                <div class="flex flex-wrap justify-center gap-1.5 mt-2">${badges}</div>
            </div>
            ${c.hold ? `<div class="mx-5 mt-4 p-3 rounded-lg bg-danger/10 border border-danger/30 text-sm text-danger"><i class="mgc_lock_line"></i> <strong>On hold</strong> — ${esc(c.hold)}</div>` : ''}
            <div class="px-5 py-3 divide-y divide-gray-100 dark:divide-gray-700">
                ${line('mgc_user_3_line', 'Age · Gender', [c.age != null ? c.age + ' yrs' : null, c.gender].filter(Boolean).join(' · '))}
                ${line('mgc_location_line', 'Address', c.address)}
                ${line('mgc_phone_line', 'Contact', c.contact)}
                ${line('mgc_home_3_line', 'Household', c.household)}
                ${line('mgc_IDcard_line', 'Barangay ID', c.id_valid ? 'Valid until ' + c.id_valid : null)}
            </div>
            <div class="px-5 pb-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1 mt-2">Recent activity</p>
                ${recentHtml}
                ${(res.items || []).length > 5 ? `<p class="text-xs text-gray-400 mt-2">${(res.items.length - 5).toLocaleString()} more under All transactions.</p>` : ''}
            </div>`;
    }

    document.addEventListener('click', e => {
        const b = e.target.closest('.quick-view');
        if (!b) return;
        e.preventDefault();
        openQuickView(b.dataset.id);
    });
    document.getElementById('qv-close').addEventListener('click', closeQuickView);
    document.getElementById('qv-backdrop').addEventListener('click', closeQuickView);
    document.getElementById('qv-activity').addEventListener('click', () => {
        const id = qvId;
        closeQuickView();
        if (id) openCitizenActivity({ id });
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !qv.classList.contains('hidden')) closeQuickView(); });

    // First paint
    paintStatCards();
    syncChecks();

}); // end DOMContentLoaded

// ── Export Modal ──────────────────────────────────────────
(function () {
    const openBtn = document.getElementById('open-export-modal');
    if (!openBtn) return; // user lacks citizens.export permission — button/modal not rendered

    const modal     = document.getElementById('export-modal');
    const backdrop  = document.getElementById('export-backdrop');
    const closeBtn  = document.getElementById('close-export-modal');
    const cancelBtn = document.getElementById('cancel-export-modal');
    const form      = document.getElementById('export-form');
    const submitLabel = document.getElementById('export-submit-label');
    const fieldCount  = document.getElementById('export-field-count');

    // What to export: the ticked citizens, or whatever the list is filtered by
    // right now (the address bar is kept in step with the list).
    const scope      = document.getElementById('export-scope');
    const matchCount = document.getElementById('export-match-count');
    const matchLabel = document.getElementById('export-match-label');
    const LIST_ONLY  = ['page', 'per_page', 'view', 'sort', 'dir'];

    function addHidden(name, value) {
        const i = document.createElement('input');
        i.type = 'hidden'; i.name = name; i.value = value;
        scope.append(i);
    }

    function openModal(ids) {
        scope.replaceChildren();
        if (Array.isArray(ids) && ids.length) {
            ids.forEach(id => addHidden('ids[]', id));
            matchCount.textContent = ids.length.toLocaleString();
            matchLabel.textContent = ids.length === 1 ? 'selected citizen' : 'selected citizens';
        } else {
            const params = new URLSearchParams(window.location.search);
            if (!params.has('voters')) params.set('voters', '1');       // the list's default
            for (const [k, v] of params.entries()) {
                if (!LIST_ONLY.includes(k) && v !== '') addHidden(k, v);
            }
            if (window.citizenListTotal !== undefined) matchCount.textContent = window.citizenListTotal.toLocaleString();
            matchLabel.textContent = 'records match current filter';
        }
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        updateCount();
    }
    window.openExportModal = openModal;

    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    openBtn.addEventListener('click', () => openModal());
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });

    // Update submit button label based on selected format
    document.querySelectorAll('input[name="format"]').forEach(radio => {
        radio.addEventListener('change', function () {
            submitLabel.textContent = 'Export ' + this.value.toUpperCase();
        });
    });

    // Update selected field count in footer
    function updateCount() {
        const checked = document.querySelectorAll('.export-field-checkbox:checked').length;
        fieldCount.textContent = checked + ' field' + (checked !== 1 ? 's' : '') + ' selected';
    }

    document.querySelectorAll('.export-field-checkbox').forEach(cb => {
        cb.addEventListener('change', updateCount);
    });

    // Select All / Clear All (global)
    document.getElementById('select-all-fields').addEventListener('click', () => {
        document.querySelectorAll('.export-field-checkbox').forEach(cb => cb.checked = true);
        updateCount();
    });

    document.getElementById('deselect-all-fields').addEventListener('click', () => {
        document.querySelectorAll('.export-field-checkbox').forEach(cb => cb.checked = false);
        updateCount();
    });

    // Toggle All per group
    document.querySelectorAll('.toggle-group').forEach(btn => {
        btn.addEventListener('click', function () {
            const groupIdx   = this.dataset.group;
            const container  = document.querySelector('[data-group-fields="' + groupIdx + '"]');
            const checkboxes = container.querySelectorAll('.export-field-checkbox');
            const allChecked = [...checkboxes].every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
            updateCount();
        });
    });

    // Guard: must select at least one field
    form.addEventListener('submit', function (e) {
        const checked = document.querySelectorAll('.export-field-checkbox:checked').length;
        if (checked === 0) {
            e.preventDefault();
            Swal.fire({ icon: 'info', title: 'No fields chosen', text: 'Please select at least one field to export.',
                didOpen: () => document.querySelector('.swal2-confirm').style.setProperty('background-color', '#727cf5', 'important') });
            return;
        }
        closeModal();
    });
})();
</script>
@endsection
