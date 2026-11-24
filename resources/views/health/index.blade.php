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
            <div class="flex-1 min-w-[13rem]">
                <label class="text-xs text-gray-500 dark:text-gray-400 font-medium block mb-1.5">Search</label>
                <div class="relative">
                    <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Patient, complaint, findings…" class="form-input ps-9 text-sm w-full">
                </div>
            </div>
            <div class="min-w-[11rem]">
                <label class="text-xs text-gray-500 dark:text-gray-400 font-medium block mb-1.5">Type of check-up</label>
                <select name="type" class="form-select text-sm w-full" onchange="this.form.submit()">
                    <option value="">All types</option>
                    @foreach($types as $t)
                    <option value="{{ $t }}" @selected(request('type') === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[10rem]">
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
                @if(request()->hasAny(['search', 'from', 'to', 'flag', 'type', 'period']))
                <a href="{{ route('health.index') }}" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white"><i class="mgc_close_line me-1"></i>Clear</a>
                @endif
            </div>
        </div>

        {{-- Date range: quick picks, or Custom with From / To --}}
        <input type="hidden" name="period" id="period-input" value="{{ $period }}">
        <div class="flex flex-wrap items-center gap-3 mt-3">
            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Date</span>
            <div class="inline-flex flex-wrap rounded-lg border border-gray-200 dark:border-gray-700 p-1 gap-1" role="radiogroup" aria-label="Date range">
                @foreach(['' => 'All dates', 'today' => 'Today', 'week' => '1 week', 'month' => '1 month', 'custom' => 'Custom'] as $key => $label)
                <button type="button" data-period="{{ $key }}" role="radio" aria-checked="{{ $period === $key ? 'true' : 'false' }}"
                        class="period-btn px-3 py-1.5 rounded-md text-sm font-medium transition {{ $period === $key ? 'bg-primary text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">{{ $label }}</button>
                @endforeach
            </div>
            <div id="custom-range" class="flex flex-wrap items-center gap-2 {{ $period === 'custom' ? '' : 'hidden' }}">
                <input type="date" name="from" value="{{ $period === 'custom' ? $from : '' }}" class="form-input text-sm py-1.5" aria-label="From">
                <span class="text-gray-400 text-sm">to</span>
                <input type="date" name="to" value="{{ $period === 'custom' ? $to : '' }}" class="form-input text-sm py-1.5" aria-label="To">
                <button type="submit" class="btn btn-sm bg-primary text-white">Apply</button>
            </div>
            @if($from || $to)
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ $from ? date('M d, Y', strtotime($from)) : 'Start' }} – {{ $to ? date('M d, Y', strtotime($to)) : 'Today' }}
            </span>
            @endif
        </div>
    </form>

    <div class="overflow-x-auto mt-2">
        <table class="table min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">{{ request('flag') === 'follow_up' ? 'Follow-up' : 'Date' }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Type</th>
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
                    <td class="px-5 py-3 align-top">
                        @if($c->checkup_type)
                        <a href="{{ route('health.index', array_merge(request()->except('page'), ['type' => $c->checkup_type])) }}"
                           class="inline-flex items-center py-1 px-2.5 rounded-full text-xs font-medium bg-primary/10 text-primary hover:bg-primary hover:text-white whitespace-nowrap">{{ $c->checkup_type }}</a>
                        @else<span class="text-gray-400">—</span>@endif
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
                <tr><td colspan="7" class="text-center py-12 text-gray-400">
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

@push('inline-scripts')
<script>
// Date range quick picks: Today / 1 week / 1 month apply at once; Custom shows From–To.
document.querySelectorAll('.period-btn').forEach(b => b.addEventListener('click', () => {
    const form = b.closest('form');
    document.getElementById('period-input').value = b.dataset.period;
    if (b.dataset.period === 'custom') {
        document.querySelectorAll('.period-btn').forEach(x => {
            const on = x === b;
            x.classList.toggle('bg-primary', on); x.classList.toggle('text-white', on);
            x.classList.toggle('text-gray-600', !on); x.setAttribute('aria-checked', on);
        });
        document.getElementById('custom-range').classList.remove('hidden');
        form.querySelector('input[name="from"]').focus();
        return;
    }
    form.querySelectorAll('input[name="from"], input[name="to"]').forEach(i => i.value = '');
    form.submit();
}));
</script>
@endpush
