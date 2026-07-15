@extends('layouts.vertical', ['title' => 'Citizens', 'sub_title' => 'Citizen Management', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@push('skeleton')
@include('partials._skeleton_table', ['rows' => 10, 'cols' => 6])
@endpush

@section('content')
<div class="grid grid-cols-12 gap-6">
    <div class="col-span-12">

        {{-- Page Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <h4 class="text-slate-900 dark:text-slate-100 text-lg font-semibold">Citizen Records</h4>
            <div class="flex items-center gap-2">
                @can('citizens.export')
                <button type="button" id="open-export-modal"
                    class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                    <i class="mgc_file_download_line me-1"></i> Export
                </button>
                @endcan
                <a href="{{ route('citizens.create') }}" class="btn bg-primary text-white">
                    <i class="mgc_add_line me-1"></i> Add Citizen
                </a>
                <a href="{{ route('citizens.create-minor') }}" class="btn bg-amber-500 text-white">
                    <i class="mgc_user_2_line me-1"></i> Register Minor
                </a>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-4">

            <div class="card p-5 flex flex-col items-center gap-2 text-center">
                <div class="w-12 h-12 rounded-xl bg-primary/15 flex items-center justify-center">
                    <i class="mgc_group_line text-2xl text-primary"></i>
                </div>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats['total']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Total Citizens</p>
            </div>

            <div class="card p-5 flex flex-col items-center gap-2 text-center">
                <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <i class="mgc_male_line text-2xl text-blue-500"></i>
                </div>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats['male']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Male</p>
            </div>

            <div class="card p-5 flex flex-col items-center gap-2 text-center">
                <div class="w-12 h-12 rounded-xl bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center">
                    <i class="mgc_female_line text-2xl text-pink-500"></i>
                </div>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats['female']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Female</p>
            </div>

            <div class="card p-5 flex flex-col items-center gap-2 text-center">
                <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <i class="mgc_check_circle_line text-2xl text-green-500"></i>
                </div>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats['voters']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Voters</p>
            </div>

            <div class="card p-5 flex flex-col items-center gap-2 text-center">
                <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <i class="mgc_wheelchair_line text-2xl text-purple-500"></i>
                </div>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats['pwd']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">PWD</p>
            </div>

            <div class="card p-5 flex flex-col items-center gap-2 text-center">
                <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <i class="mgc_time_line text-2xl text-amber-500"></i>
                </div>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats['senior']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Senior (60+)</p>
            </div>

        </div>

        {{-- Advanced Filter Card --}}
        <div class="card mb-4">
            <div class="card-header cursor-pointer" id="filter-toggle">
                <div class="flex justify-between items-center">
                    <h5 class="card-title">
                        <i class="mgc_filter_line me-2"></i> Advanced Filter
                        @php
                            $activeFilters = collect($filters ?? [])->filter(fn($v) => $v !== '' && $v !== null && $v !== [])->count();
                        @endphp
                        <span id="filter-active-badge" class="ms-2 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-primary rounded-full {{ $activeFilters > 0 ? '' : 'hidden' }}">{{ $activeFilters }}</span>
                    </h5>
                    <i class="mgc_down_line text-lg transition-transform duration-200" id="filter-chevron"></i>
                </div>
            </div>

            <div id="filter-body" class="{{ ($activeFilters ?? 0) > 0 ? '' : 'hidden' }}">
                <form method="GET" action="{{ route('citizens.index') }}" id="filter-form">
                    <div class="grid lg:grid-cols-4 md:grid-cols-2 grid-cols-1 gap-4 p-6">

                        {{-- Search --}}
                        <div class="lg:col-span-2">
                            <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Search</label>
                            <div class="relative">
                                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                                    class="form-input ps-9"
                                    placeholder="Name, contact, email...">
                                <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>

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

                        {{-- Age Range --}}
                        <div>
                            <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Age Range</label>
                            <div class="flex gap-2 items-center">
                                <input type="number" name="age_min" value="{{ $filters['age_min'] ?? '' }}"
                                    class="form-input w-full" placeholder="Min" min="0" max="150">
                                <span class="text-gray-400 text-sm">–</span>
                                <input type="number" name="age_max" value="{{ $filters['age_max'] ?? '' }}"
                                    class="form-input w-full" placeholder="Max" min="0" max="150">
                            </div>
                        </div>

                        {{-- Household Search --}}
                        <div class="lg:col-span-2">
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
                        <div class="lg:col-span-4 flex flex-wrap gap-6 pt-2">

                            {{-- Active Status --}}
                            <div>
                                <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Status</label>
                                <select name="is_active" class="form-select">
                                    <option value="">All</option>
                                    <option value="1" {{ ($filters['is_active'] ?? '1') == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ ($filters['is_active'] ?? '') == '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>

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
                        <p id="record-count-text" class="text-xs text-gray-500 mt-0.5">
                            Showing {{ $citizens->firstItem() ?? 0 }}–{{ $citizens->lastItem() ?? 0 }} of {{ $citizens->total() }} records
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <select id="per-page-select" class="form-select text-sm py-1.5">
                            @foreach([15, 25, 50, 100] as $pp)
                                <option value="{{ $pp }}" {{ request('per_page', 15) == $pp ? 'selected' : '' }}>{{ $pp }} / page</option>
                            @endforeach
                        </select>
                    </div>
                </div>
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

{{-- Delete Confirmation Form (hidden) --}}
<form id="delete-form" method="POST" action="" class="hidden">
    @csrf
    @method('DELETE')
</form>

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
                    <span class="text-xs text-gray-400 ms-1">({{ $citizens->total() }} records match current filter)</span>
                </div>
                <button type="button" id="close-export-modal"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <i class="mgc_close_line text-xl"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <form id="export-form" method="POST" action="{{ route('citizens.export') }}" class="flex flex-col overflow-hidden">
                @csrf

                {{-- Pass active filters as hidden inputs --}}
                @foreach($filters ?? [] as $key => $value)
                    @if($value !== '' && $value !== null)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

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

@endsection

@section('script')
<script>
window.addEventListener('DOMContentLoaded', () => {

    // ── Filter toggle ──────────────────────────────────────
    document.getElementById('filter-toggle').addEventListener('click', () => {
        document.getElementById('filter-body').classList.toggle('hidden');
        document.getElementById('filter-chevron').classList.toggle('rotate-180');
    });

    // ── Delete confirmation ────────────────────────────────
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-delete');
        if (!btn) return;
        const id   = btn.dataset.id;
        const name = btn.dataset.name;
        if (confirm('Delete citizen "' + name + '"? This cannot be undone.')) {
            document.getElementById('delete-form').setAttribute('action', '/citizens/' + id);
            document.getElementById('delete-form').submit();
        }
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

    function getParams() {
        const data = new FormData(form);
        const params = new URLSearchParams();
        for (const [k, v] of data.entries()) {
            if (v !== '') params.set(k, v);
        }
        params.set('per_page', perPage.value);
        params.set('page', currentPage);
        return params;
    }

    function updateActiveCount() {
        const data = new FormData(form);
        let count = 0;
        for (const [, v] of data.entries()) { if (v !== '') count++; }
        const badge = document.getElementById('filter-active-badge');
        if (badge) {
            badge.textContent = count;
            badge.classList.toggle('hidden', count === 0);
        }
    }

    function fetchTable(resetPage) {
        if (resetPage) currentPage = 1;
        overlay.classList.remove('hidden');

        const params = getParams();
        history.replaceState(null, '', AJAX_URL + '?' + params.toString());

        fetch(AJAX_URL + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            wrap.innerHTML = res.html;
            overlay.classList.add('hidden');
            updateActiveCount();
            // Sync header record count
            const innerCount = wrap.querySelector('#record-count-text');
            const headerCount = document.getElementById('record-count-text');
            if (innerCount && headerCount && innerCount !== headerCount) {
                headerCount.textContent = innerCount.textContent;
                innerCount.remove();
            }
            // Intercept pagination links in swapped HTML
            attachPaginationLinks();
        })
        .catch(() => overlay.classList.add('hidden'));
    }

    function attachPaginationLinks() {
        wrap.querySelectorAll('a[href*="page="]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                currentPage = parseInt(new URL(a.href).searchParams.get('page')) || 1;
                fetchTable(false);
            });
        });
    }

    // Apply button
    applyBtn.addEventListener('click', () => fetchTable(true));

    // Also allow Enter key in any filter input to trigger apply
    form.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); fetchTable(true); }
    });

    // Per-page fires immediately (no ambiguity)
    perPage.addEventListener('change', () => fetchTable(true));

    // Clear — reset all fields then fetch
    clearBtn.addEventListener('click', () => {
        form.querySelectorAll('input[type="text"], input[type="number"]').forEach(i => i.value = '');
        form.querySelectorAll('select').forEach(s => s.value = '');
        fetchTable(true);
    });

    // Initial server-rendered pagination links
    attachPaginationLinks();

    // Keep overlay inside wrap after every innerHTML swap
    const observer = new MutationObserver(() => {
        if (!wrap.contains(overlay)) wrap.prepend(overlay);
    });
    observer.observe(wrap, { childList: true });

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

    function openModal() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        updateCount();
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    openBtn.addEventListener('click', openModal);
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
            alert('Please select at least one field to export.');
            return;
        }
        closeModal();
    });
})();
</script>
@endsection
