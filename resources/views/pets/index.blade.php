@extends('layouts.vertical', [
    'title'         => 'Pets',
    'sub_title'     => 'Citizen',
    'sub_title_url' => route('citizens.index'),
    'tagline'       => 'Pets owned by residents, with photo, kind and anti-rabies record.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif

{{-- ── Summary ── --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    @foreach([
        ['Active pets',          $stats['total'],  'mgc_bone_line',   'bg-primary/10', 'text-primary', ['status' => 'active']],
        ['Pet owners',           $stats['owners'], 'mgc_user_3_line', 'bg-info/10',    'text-info',    ['status' => 'active']],
        ['Anti-rabies due',      $stats['due'],    'mgc_alert_line',  'bg-warning/10', 'text-warning', ['vaccine' => 'due']],
    ] as [$label, $count, $icon, $bg, $fg, $f])
    <a href="{{ route('pets.index', $f) }}" class="card p-5 hover:shadow-md transition">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full {{ $bg }} flex items-center justify-center shrink-0">
                <i class="{{ $icon }} {{ $fg }} text-2xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($count) }}</p>
            </div>
        </div>
    </a>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <div class="flex justify-between items-center gap-3 flex-wrap">
            <h4 class="card-title">Pets</h4>
            @can('pets.create')
            <a href="{{ route('pets.create') }}" class="btn bg-primary text-white"><i class="mgc_add_line me-1"></i>Add pet</a>
            @endcan
        </div>
    </div>

    {{-- Category chips --}}
    <div class="px-6 pt-4 flex flex-wrap gap-2">
        @php $catNow = (string) ($filters['category'] ?? ''); @endphp
        <a href="{{ route('pets.index', \Illuminate\Support\Arr::except($filters, 'category')) }}"
           class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-sm font-medium border transition
                  {{ $catNow === '' ? 'bg-primary text-white border-primary' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-primary hover:text-primary' }}">
            All <span class="text-xs opacity-80">{{ number_format($stats['total']) }}</span>
        </a>
        @foreach($categories as $cat)
        @php $n = (int) ($byCategory[$cat->id] ?? 0); @endphp
        @continue(!$n && !$cat->is_active && $catNow !== (string) $cat->id)
        <a href="{{ route('pets.index', array_merge($filters, ['category' => $cat->id])) }}"
           class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-sm font-medium border transition
                  {{ $catNow === (string) $cat->id ? 'bg-primary text-white border-primary' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-primary hover:text-primary' }}">
            <i class="{{ \App\Models\PetCategory::iconFor($cat->name) }}"></i>{{ $cat->name }}
            <span class="text-xs opacity-80">{{ number_format($n) }}</span>
        </a>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" class="px-6 pt-4 pb-2">
        @if($catNow !== '')<input type="hidden" name="category" value="{{ $catNow }}">@endif
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[13rem]">
                <label class="text-xs text-gray-500 dark:text-gray-400 font-medium block mb-1.5">Search</label>
                <div class="relative">
                    <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Pet name, breed, color or owner" class="form-input ps-9 text-sm w-full">
                </div>
            </div>
            <div class="min-w-[10rem]">
                <label class="text-xs text-gray-500 dark:text-gray-400 font-medium block mb-1.5">Status</label>
                <select name="status" class="form-select text-sm w-full" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach(\App\Models\Pet::STATUSES as $key => [$label])
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[11rem]">
                <label class="text-xs text-gray-500 dark:text-gray-400 font-medium block mb-1.5">Anti-rabies</label>
                <select name="vaccine" class="form-select text-sm w-full" onchange="this.form.submit()">
                    <option value="">Any</option>
                    <option value="due" @selected(($filters['vaccine'] ?? '') === 'due')>Due or not recorded</option>
                </select>
            </div>
            <div class="flex gap-2 pb-0.5">
                <button type="submit" class="btn bg-primary text-white"><i class="mgc_search_line me-1"></i>Filter</button>
                @if(array_filter($filters))
                <a href="{{ route('pets.index') }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white"><i class="mgc_close_line me-1"></i>Clear</a>
                @endif
            </div>
        </div>
    </form>

    {{-- Cards --}}
    <div class="p-6 pt-4">
        @if($pets->isEmpty())
        <div class="text-center py-12 text-gray-400">
            <i class="mgc_bone_line text-4xl mb-2 block opacity-30"></i>No pets found.
            @can('pets.create')
            <a href="{{ route('pets.create') }}" class="block mt-3 text-sm text-primary hover:underline">Add the first pet</a>
            @endcan
        </div>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
            @foreach($pets as $pet)
            @php
                [$stLabel, $stPill, $stDot] = \App\Models\Pet::STATUSES[$pet->status] ?? \App\Models\Pet::STATUSES['active'];
                $vac = $pet->vaccineCurrent();
                $icon = \App\Models\PetCategory::iconFor($pet->category?->name);
            @endphp
            <a href="{{ route('pets.show', $pet) }}" class="group rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md hover:border-primary/40 transition bg-white dark:bg-gray-800">
                <div class="relative bg-gray-100 dark:bg-gray-700" style="aspect-ratio:1/1">
                    @if($pet->photo_url)
                    <img src="{{ $pet->photo_url }}" alt="{{ $pet->name }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover {{ $pet->status === 'deceased' ? 'grayscale' : '' }}">
                    @else
                    <div class="absolute inset-0 flex items-center justify-center text-gray-300 dark:text-gray-500"><i class="{{ $icon }} text-5xl"></i></div>
                    @endif
                    @if($pet->status !== 'active')
                    <span class="absolute top-2 left-2 inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-[11px] font-medium {{ $stPill }}">
                        <span class="w-1.5 h-1.5 inline-block {{ $stDot }} rounded-full"></span>{{ $stLabel }}
                    </span>
                    @endif
                </div>
                <div class="p-3">
                    <p class="font-semibold text-gray-800 dark:text-gray-100 truncate group-hover:text-primary">{{ $pet->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        <i class="{{ $icon }}"></i> {{ collect([$pet->category?->name, $pet->breed])->filter()->implode(' · ') ?: '—' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1" title="{{ $pet->citizen?->full_name }}">
                        <i class="mgc_user_3_line"></i> {{ $pet->citizen?->full_name ?? '—' }}
                    </p>
                    @if($pet->status === 'active')
                    <p class="text-[11px] mt-1.5 {{ $vac === true ? 'text-success' : ($vac === false ? 'text-danger' : 'text-gray-400') }}">
                        <i class="{{ $vac === true ? 'mgc_check_circle_line' : 'mgc_alert_line' }}"></i>
                        {{ $vac === true ? 'Anti-rabies up to date' : ($vac === false ? 'Anti-rabies due' : 'No anti-rabies record') }}
                    </p>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
        @if($pets->hasPages())
        <div class="mt-6">{{ $pets->links() }}</div>
        @endif
        @endif
    </div>
</div>
@endsection
