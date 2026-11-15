@extends('layouts.vertical', [
    'title'         => 'Ordinances & Resolutions',
    'sub_title'     => 'Barangay',
    'sub_title_url' => route('legislation.index'),
    'tagline'       => 'Measures enacted by the Sangguniang Barangay',
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

{{-- ── Stat cards ── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Ordinances',      $stats['ordinances'],  'mgc_book_6_line',       'bg-primary/10', 'text-primary'],
        ['Resolutions',     $stats['resolutions'], 'mgc_file_check_line',   'bg-info/10',    'text-info'],
        ['In force',        $stats['in_force'],    'mgc_flag_2_line',       'bg-success/10', 'text-success'],
        ['Pending readings',$stats['pending'],     'mgc_time_line',         'bg-warning/10', 'text-warning'],
    ] as [$label, $count, $icon, $bg, $fg])
    <div class="card p-5">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full {{ $bg }} flex items-center justify-center shrink-0">
                <i class="{{ $icon }} {{ $fg }} text-2xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($count) }}</p>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card">
    {{-- Type tabs + add buttons --}}
    <div class="card-header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <div class="flex gap-1 p-1 rounded-lg bg-gray-100 dark:bg-gray-700/60">
                @foreach(['' => 'All', 'ordinance' => 'Ordinances', 'resolution' => 'Resolutions'] as $val => $label)
                <a href="{{ route('legislation.index', array_merge(request()->except(['type', 'page']), $val ? ['type' => $val] : [])) }}"
                   class="px-3 py-1.5 rounded-md text-sm font-medium transition
                          {{ (string) request('type') === $val ? 'bg-white dark:bg-gray-800 text-primary shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:text-primary' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>
            @can('legislation.create')
            <div class="flex gap-2">
                <a href="{{ route('legislation.create', ['type' => 'resolution']) }}" class="btn bg-info/25 text-info hover:bg-info hover:text-white">
                    <i class="mgc_add_line me-1"></i>Resolution
                </a>
                <a href="{{ route('legislation.create', ['type' => 'ordinance']) }}" class="btn bg-primary text-white">
                    <i class="mgc_add_line me-1"></i>Ordinance
                </a>
            </div>
            @endcan
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="px-6 pt-4 pb-2">
        @if(request('type'))<input type="hidden" name="type" value="{{ request('type') }}">@endif
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-52">
                <label class="text-xs text-gray-500 dark:text-gray-400 font-medium block mb-1.5">Search</label>
                <div class="relative">
                    <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Number, title, author…" class="form-input ps-9 text-sm w-full">
                </div>
            </div>
            <div class="min-w-36">
                <label class="text-xs text-gray-500 dark:text-gray-400 font-medium block mb-1.5">Status</label>
                <select name="status" class="form-select text-sm">
                    <option value="">All statuses</option>
                    @foreach(\App\Models\Legislation::STATUSES as $val => $label)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-28">
                <label class="text-xs text-gray-500 dark:text-gray-400 font-medium block mb-1.5">Series</label>
                <select name="year" class="form-select text-sm">
                    <option value="">All years</option>
                    @foreach($years as $y)
                    <option value="{{ $y }}" @selected((string) request('year') === (string) $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-44">
                <label class="text-xs text-gray-500 dark:text-gray-400 font-medium block mb-1.5">Category</label>
                <select name="category" class="form-select text-sm">
                    <option value="">All categories</option>
                    @foreach(\App\Models\Legislation::CATEGORIES as $c)
                    <option value="{{ $c }}" @selected(request('category') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 pb-0.5">
                <button type="submit" class="btn bg-primary text-white"><i class="mgc_search_line me-1"></i> Filter</button>
                @if(request()->hasAny(['search', 'status', 'year', 'category']))
                <a href="{{ route('legislation.index', request()->only('type')) }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">
                    <i class="mgc_close_line me-1"></i> Clear
                </a>
                @endif
            </div>
        </div>
    </form>

    <div class="overflow-x-auto mt-2">
        <table class="table min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">No.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Author</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Approved</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Effective</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($legislations as $l)
                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 cursor-pointer" onclick="if (!event.target.closest('a')) location.href='{{ route('legislation.show', $l) }}'">
                    <td class="px-5 py-3 whitespace-nowrap align-top">
                        <a href="{{ route('legislation.show', $l) }}" class="font-mono font-semibold text-primary hover:underline">{{ $l->number }}</a>
                        <p class="text-xs mt-0.5 {{ $l->type === 'ordinance' ? 'text-primary/80' : 'text-info' }}">{{ $l->typeLabel() }}</p>
                    </td>
                    <td class="px-5 py-3 text-gray-800 dark:text-gray-200 align-top max-w-md">
                        <p class="font-medium line-clamp-2">{{ $l->title }}</p>
                        @if($l->category)<p class="text-xs text-gray-400 mt-0.5">{{ $l->category }}</p>@endif
                    </td>
                    <td class="px-5 py-3 text-gray-700 dark:text-gray-300 align-top">{{ $l->authorNames() ?: '—' }}</td>
                    <td class="px-5 py-3 align-top">
                        @include('legislation._status-badge', ['measure' => $l])
                        @if($l->status === 'repealed' && $l->repealedBy)
                        <p class="text-xs text-gray-400 mt-1">by {{ $l->repealedBy->number }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300 align-top">{{ $l->date_approved?->format('M d, Y') ?? '—' }}</td>
                    <td class="px-5 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300 align-top">{{ $l->date_effective?->format('M d, Y') ?? '—' }}</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap align-top">
                        @if($l->file_path)
                        <a href="{{ route('legislation.file', $l) }}" target="_blank" class="p-1.5 rounded text-gray-500 dark:text-gray-400 hover:text-primary hover:bg-primary/10" title="Open signed copy">
                            <i class="mgc_pdf_line text-base"></i>
                        </a>
                        @endif
                        @can('legislation.edit')
                        <a href="{{ route('legislation.edit', $l) }}" class="p-1.5 rounded text-gray-500 dark:text-gray-400 hover:text-warning hover:bg-warning/10" title="Edit">
                            <i class="mgc_edit_line text-base"></i>
                        </a>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-12 text-gray-400">
                    <i class="mgc_book_6_line text-4xl mb-2 block opacity-30"></i>
                    {{ request()->hasAny(['search', 'status', 'year', 'category', 'type']) ? 'No measures match these filters.' : 'No ordinances or resolutions recorded yet.' }}
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($legislations->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $legislations->links() }}</div>
    @endif
</div>
@endsection
