@extends('layouts.vertical', [
    'title'         => 'Health Check-ups',
    'sub_title'     => 'Services',
    'sub_title_url' => route('health.index'),
    'tagline'       => 'Libreng check-up and walk-in consultations',
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

{{-- ── Stat cards (click to filter) ── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Today',              $stats['today'],     'mgc_heartbeat_line',  'bg-primary/10', 'text-primary', ['from' => today()->toDateString(), 'to' => today()->toDateString()]],
        ['This month',         $stats['month'],     'mgc_calendar_line',   'bg-info/10',    'text-info',    ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()]],
        ['High BP this month', $stats['high_bp'],   'mgc_alert_line',      'bg-danger/10',  'text-danger',  ['flag' => 'high_bp', 'from' => now()->startOfMonth()->toDateString()]],
        ['Follow-ups (7 days)',$stats['follow_up'], 'mgc_time_line',       'bg-warning/10', 'text-warning', ['flag' => 'follow_up']],
    ] as [$label, $count, $icon, $bg, $fg, $filter])
    <a href="{{ route('health.index', $filter) }}" class="card p-5 hover:shadow-md transition">
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
        <div class="flex justify-between items-center">
            <h4 class="card-title">Check-ups</h4>
            @can('health.create')
            <a href="{{ route('health.create') }}" class="btn bg-primary text-white"><i class="mgc_add_line me-1"></i>New check-up</a>
            @endcan
        </div>
    </div>

    <form method="GET" class="px-6 pt-4 pb-2">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-52">
                <label class="text-xs text-gray-500 dark:text-gray-400 font-medium block mb-1.5">Search</label>
                <div class="relative">
                    <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Patient, complaint, findings…" class="form-input ps-9 text-sm w-full">
                </div>
            </div>
            <div>
                <label class="text-xs text-gray-500 dark:text-gray-400 font-medium block mb-1.5">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-input text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 dark:text-gray-400 font-medium block mb-1.5">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-input text-sm">
            </div>
            <div class="min-w-40">
                <label class="text-xs text-gray-500 dark:text-gray-400 font-medium block mb-1.5">Show</label>
                <select name="flag" class="form-select text-sm">
                    <option value="">All check-ups</option>
                    <option value="high_bp" @selected(request('flag') === 'high_bp')>High blood pressure</option>
                    <option value="fever" @selected(request('flag') === 'fever')>Fever</option>
                    <option value="high_sugar" @selected(request('flag') === 'high_sugar')>High blood sugar</option>
                    <option value="follow_up" @selected(request('flag') === 'follow_up')>Upcoming follow-ups</option>
                </select>
            </div>
            <div class="flex gap-2 pb-0.5">
                <button type="submit" class="btn bg-primary text-white"><i class="mgc_search_line me-1"></i>Filter</button>
                @if(request()->hasAny(['search', 'from', 'to', 'flag']))
                <a href="{{ route('health.index') }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white"><i class="mgc_close_line me-1"></i>Clear</a>
                @endif
            </div>
        </div>
    </form>

    <div class="overflow-x-auto mt-2">
        <table class="table min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">{{ request('flag') === 'follow_up' ? 'Follow-up' : 'Date' }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">BP</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Complaint</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Notes</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($checkups as $c)
                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 cursor-pointer" onclick="if (!event.target.closest('a')) location.href='{{ route('health.show', $c) }}'">
                    <td class="px-5 py-3 whitespace-nowrap align-top text-gray-700 dark:text-gray-300">
                        @if(request('flag') === 'follow_up')
                        <p class="font-medium text-gray-800 dark:text-gray-100">{{ $c->follow_up_on->format('M d, Y') }}</p>
                        <p class="text-xs text-gray-400">seen {{ $c->checked_at->format('M d, Y') }}</p>
                        @else
                        <p>{{ $c->checked_at->format('M d, Y') }}</p>
                        <p class="text-xs text-gray-400">{{ $c->checked_at->format('g:i A') }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-3 align-top">
                        <a href="{{ route('health.show', $c) }}" class="font-medium text-gray-800 dark:text-gray-100 hover:text-primary">{{ $c->patientName() }}</a>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ collect([$c->patientAge() !== null ? $c->patientAge() . ' yrs' : null, $c->patientSex(), $c->isWalkIn() ? 'Walk-in' : null])->filter()->implode(' · ') }}
                        </p>
                    </td>
                    <td class="px-5 py-3 align-top whitespace-nowrap">
                        @if($c->bloodPressure())
                        <p class="font-medium text-gray-800 dark:text-gray-100">{{ $c->bloodPressure() }}</p>
                        @else<span class="text-gray-400">—</span>@endif
                    </td>
                    <td class="px-5 py-3 align-top text-gray-700 dark:text-gray-300 max-w-xs">
                        <p class="line-clamp-2">{{ $c->complaint ?: '—' }}</p>
                    </td>
                    <td class="px-5 py-3 align-top">
                        <div class="flex flex-wrap gap-1">
                            @foreach($c->alerts() as $a)
                            @include('health._flag', ['flag' => $a])
                            @endforeach
                            @if($c->medicines->isNotEmpty())
                            <span class="inline-flex items-center gap-1 py-1 px-2.5 rounded-full text-xs font-medium bg-primary/25 text-sky-800 whitespace-nowrap">
                                <i class="mgc_box_3_line"></i>{{ $c->medicines->count() }} medicine{{ $c->medicines->count() > 1 ? 's' : '' }}
                            </span>
                            @endif
                            @if($c->referred_to)
                            <span class="inline-flex items-center gap-1 py-1 px-2.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 whitespace-nowrap">Referred</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-3 text-right whitespace-nowrap align-top">
                        @can('health.edit')
                        <a href="{{ route('health.edit', $c) }}" class="p-1.5 rounded text-gray-500 dark:text-gray-400 hover:text-warning hover:bg-warning/10" title="Edit">
                            <i class="mgc_edit_line text-base"></i>
                        </a>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-12 text-gray-400">
                    <i class="mgc_heartbeat_line text-4xl mb-2 block opacity-30"></i>
                    {{ request()->hasAny(['search', 'from', 'to', 'flag']) ? 'No check-ups match these filters.' : 'No check-ups recorded yet.' }}
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($checkups->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $checkups->links() }}</div>
    @endif
</div>
@endsection
