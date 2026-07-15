@extends('layouts.vertical', [
    'title'     => 'Blotter Records',
    'sub_title' => 'Barangay',
    'tagline'   => 'Manage and track all barangay blotter entries',
    'mode'      => $mode ?? '',
    'demo'      => $demo ?? '',
])

@section('content')

@if(session('success'))
<div class="mb-5 p-4 rounded-xl bg-success/10 border border-success/20 flex gap-3">
    <i class="mgc_check_circle_line text-success text-lg shrink-0 mt-0.5"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif
@if(session('error'))
<div class="mb-5 p-4 rounded-xl bg-danger/10 border border-danger/20 flex gap-3">
    <i class="mgc_alert_line text-danger text-lg shrink-0 mt-0.5"></i>
    <p class="text-sm text-danger font-medium">{{ session('error') }}</p>
</div>
@endif

{{-- ── Page Header ── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h4 class="text-base font-bold text-gray-800 dark:text-gray-100">Blotter Records</h4>
        <p class="text-xs text-gray-400 mt-0.5">{{ number_format($blotters->total()) }} total {{ Str::plural('entry', $blotters->total()) }}</p>
    </div>
    <a href="{{ route('blotters.create') }}" class="btn bg-primary text-white">
        <i class="mgc_add_line me-1.5"></i> File Blotter
    </a>
</div>

{{-- ── Stat Cards ── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card p-5">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0">
                <i class="mgc_file_line text-xl text-gray-400"></i>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide">Total</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats['total']) }}</p>
            </div>
        </div>
    </div>
    <div class="card p-5">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <i class="mgc_inbox_line text-xl text-primary"></i>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide">Filed</p>
                <p class="text-2xl font-bold text-primary">{{ number_format($stats['filed']) }}</p>
            </div>
        </div>
    </div>
    <div class="card p-5">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-warning/10 flex items-center justify-center shrink-0">
                <i class="mgc_time_line text-xl text-warning"></i>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide">Ongoing</p>
                <p class="text-2xl font-bold text-warning">{{ number_format($stats['ongoing']) }}</p>
            </div>
        </div>
    </div>
    <div class="card p-5">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-success/10 flex items-center justify-center shrink-0">
                <i class="mgc_check_circle_line text-xl text-success"></i>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide">Settled</p>
                <p class="text-2xl font-bold text-success">{{ number_format($stats['settled']) }}</p>
            </div>
        </div>
    </div>
</div>

{{-- ── Filters ── --}}
<div class="card overflow-hidden mb-5">
    <div class="flex items-center gap-3 px-5 py-3.5 bg-primary/5 border-b border-primary/10">
        <i class="mgc_filter_line text-primary text-sm"></i>
        <h5 class="text-sm font-semibold text-primary tracking-wide">Filter Records</h5>
        @if(request()->hasAny(['search','status','type','date_from','date_to']))
        <span class="ms-auto inline-flex items-center gap-1 text-xs text-danger bg-danger/10 rounded-full px-2 py-0.5">
            <span class="w-1.5 h-1.5 rounded-full bg-danger"></span> Filtered
        </span>
        @endif
    </div>
    <form method="GET" class="p-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-52">
                <label class="text-xs text-gray-500 font-medium block mb-1.5">Search</label>
                <div class="relative">
                    <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Blotter no., name, location…"
                        class="form-input ps-9 text-sm w-full">
                </div>
            </div>
            <div class="min-w-36">
                <label class="text-xs text-gray-500 font-medium block mb-1.5">Status</label>
                <select name="status" class="form-select text-sm">
                    <option value="">All Statuses</option>
                    @foreach(\App\Models\Blotter::STATUSES as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-36">
                <label class="text-xs text-gray-500 font-medium block mb-1.5">Type</label>
                <select name="type" class="form-select text-sm">
                    <option value="">All Types</option>
                    @foreach(\App\Models\Blotter::TYPES as $val => $label)
                        <option value="{{ $val }}" {{ request('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 font-medium block mb-1.5">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 font-medium block mb-1.5">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input text-sm">
            </div>
            <div class="flex gap-2 pb-0.5">
                <button type="submit" class="btn bg-primary text-white">
                    <i class="mgc_search_line me-1"></i> Filter
                </button>
                @if(request()->hasAny(['search','status','type','date_from','date_to']))
                <a href="{{ route('blotters.index') }}"
                   class="btn bg-dark/10 text-slate-600 dark:text-slate-300 hover:bg-dark/20">
                    <i class="mgc_close_line me-1"></i> Clear
                </a>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- ── Table ── --}}
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Blotter No.</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Filed</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Incident</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Complainant</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Respondent</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 w-20"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($blotters as $b)
                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">

                    {{-- Blotter No --}}
                    <td class="px-4 py-3">
                        <a href="{{ route('blotters.show', $b) }}"
                           class="font-mono text-sm font-semibold text-primary hover:underline">
                            {{ $b->blotter_no }}
                        </a>
                    </td>

                    {{-- Filed date --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $b->filed_date->format('M d, Y') }}</p>
                    </td>

                    {{-- Incident date + time --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $b->incident_date->format('M d, Y') }}</p>
                        @if($b->incident_time)
                        <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($b->incident_time)->format('g:i A') }}</p>
                        @endif
                    </td>

                    {{-- Type --}}
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-md px-2 py-1 whitespace-nowrap">
                            {{ $b->typeLabel() }}
                        </span>
                    </td>

                    {{-- Complainant --}}
                    <td class="px-4 py-3 max-w-[160px]">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate" title="{{ $b->partyNames('complainant') }}">
                            {{ $b->partyNames('complainant') }}
                        </p>
                    </td>

                    {{-- Respondent --}}
                    <td class="px-4 py-3 max-w-[160px]">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate" title="{{ $b->partyNames('respondent') }}">
                            {{ $b->partyNames('respondent') }}
                        </p>
                    </td>

                    {{-- Status badge --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $b->statusColor() }}">
                            {{ $b->statusLabel() }}
                        </span>
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1 justify-end">
                            <a href="{{ route('blotters.show', $b) }}"
                               class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 hover:text-primary hover:bg-primary/10 transition"
                               title="View">
                                <i class="mgc_eye_line text-sm"></i>
                            </a>
                            <a href="{{ route('blotters.edit', $b) }}"
                               class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 hover:text-primary hover:bg-primary/10 transition"
                               title="Edit">
                                <i class="mgc_edit_line text-sm"></i>
                            </a>
                        </div>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-16 text-center">
                        <div class="flex flex-col items-center gap-2 text-gray-400">
                            <i class="mgc_file_search_line text-5xl opacity-30"></i>
                            <p class="text-sm font-medium">No blotter records found</p>
                            @if(request()->hasAny(['search','status','type','date_from','date_to']))
                            <p class="text-xs">Try adjusting your filters</p>
                            <a href="{{ route('blotters.index') }}" class="btn btn-sm bg-primary/10 text-primary mt-1">Clear Filters</a>
                            @else
                            <p class="text-xs">File the first blotter entry to get started</p>
                            <a href="{{ route('blotters.create') }}" class="btn btn-sm bg-primary text-white mt-1">
                                <i class="mgc_add_line me-1"></i> File Blotter
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($blotters->hasPages())
    <div class="flex flex-wrap items-center justify-between gap-4 px-5 py-4 border-t border-gray-100 dark:border-gray-700">
        <p class="text-sm text-gray-400">
            Showing {{ $blotters->firstItem() }}–{{ $blotters->lastItem() }} of {{ number_format($blotters->total()) }}
        </p>
        {{ $blotters->appends(request()->query())->links('vendor.pagination.konrix') }}
    </div>
    @endif
</div>

@endsection
