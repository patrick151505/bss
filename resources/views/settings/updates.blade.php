@extends('layouts.vertical', [
    'title'         => 'Version & Updates',
    'sub_title'     => 'Settings',
    'sub_title_url' => route('settings.index'),
    'tagline'       => 'What is new in each version of the system.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@php
    $current = $releases->first();
    $types = [
        'new'      => ['New',      'bg-green-100 text-green-800',   'bg-green-400',  'mgc_sparkles_line'],
        'improved' => ['Improved', 'bg-sky-100 text-sky-800',       'bg-sky-400',    'mgc_arrow_up_circle_line'],
        'fixed'    => ['Fixed',    'bg-yellow-100 text-yellow-800', 'bg-yellow-400', 'mgc_tool_line'],
    ];
    $date = fn($d) => \Illuminate\Support\Carbon::parse($d);
@endphp

@section('content')
<div class="grid grid-cols-12 gap-6">

    <div class="col-span-12 lg:col-span-3 xl:col-span-2">
        @include('settings.partials.subnav')
    </div>

    <div class="col-span-12 lg:col-span-9 xl:col-span-10 space-y-6">

        {{-- ── Current version ── --}}
        <div class="card p-6">
            <div class="flex flex-wrap items-start gap-5">
                <div class="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                    <i class="mgc_rocket_line text-primary text-3xl"></i>
                </div>
                <div class="flex-1 min-w-[240px]">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Current version</p>
                    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 mt-0.5">
                        <h2 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Version {{ $current['version'] }}</h2>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Released {{ $date($current['date'])->format('F d, Y') }}</span>
                    </div>
                    <p class="text-base font-medium text-gray-700 dark:text-gray-200 mt-2">{{ $current['title'] }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-3xl">{{ $current['summary'] }}</p>
                    <div class="flex flex-wrap gap-2 mt-4">
                        @foreach($types as $key => [$label, $pill, $dot])
                        @if(!empty($current['counts'][$key]))
                        <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium {{ $pill }}">
                            <span class="w-1.5 h-1.5 inline-block {{ $dot }} rounded-full"></span>{{ $current['counts'][$key] }} {{ strtolower($label) }}
                        </span>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Filters ── --}}
        <div class="card p-4 grid gap-3 md:grid-cols-[auto_13rem_1fr] items-center">
            <div class="inline-flex w-max rounded-lg border border-gray-200 dark:border-gray-700 p-1 gap-1" role="radiogroup" aria-label="Type of change">
                @foreach(['' => 'All'] + array_map(fn($t) => $t[0], $types) as $key => $label)
                <button type="button" data-type-filter="{{ $key }}"
                        class="type-filter px-3 py-1.5 rounded-md text-sm font-medium transition {{ $key === '' ? 'bg-primary text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">{{ $label }}</button>
                @endforeach
            </div>
            <select id="module-filter" class="form-select text-sm w-full" aria-label="Module">
                <option value="">All modules</option>
                @foreach($modules as $m)
                <option value="{{ $m }}">{{ $m }}</option>
                @endforeach
            </select>
            <div class="relative">
                <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="search" id="text-filter" placeholder="Search changes…" class="form-input text-sm ps-9 w-full">
            </div>
        </div>

        {{-- ── Releases ── --}}
        <div class="relative space-y-5" id="releases">
            @foreach($releases as $r)
            @php $open = $loop->first; $id = 'rel-' . str_replace('.', '-', $r['version']); @endphp
            <div class="card release" data-version="{{ $r['version'] }}">
                <button type="button" onclick="toggleAccordion('{{ $id }}-body', '{{ $id }}-chev')" aria-expanded="{{ $open ? 'true' : 'false' }}"
                        class="w-full text-left p-5 flex flex-wrap items-center gap-x-4 gap-y-2">
                    <span class="inline-flex items-center justify-center min-w-[64px] py-1.5 px-3 rounded-lg text-sm font-bold
                                 {{ $loop->first ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200' }}">v{{ $r['version'] }}</span>
                    <span class="flex-1 min-w-[200px]">
                        <span class="block font-semibold text-gray-800 dark:text-gray-100">
                            {{ $r['title'] }}
                            @if($loop->first)<span class="ms-1 align-middle py-0.5 px-2 rounded-full text-[11px] font-medium bg-primary/10 text-primary">Current</span>@endif
                        </span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $date($r['date'])->format('M d, Y') }} · {{ count($r['changes']) }} change{{ count($r['changes']) === 1 ? '' : 's' }}
                        </span>
                    </span>
                    <span class="hidden sm:flex gap-1.5">
                        @foreach($types as $key => [$label, $pill, $dot])
                        @if(!empty($r['counts'][$key]))
                        <span class="inline-flex items-center gap-1 py-0.5 px-2 rounded-full text-[11px] font-medium {{ $pill }}">{{ $r['counts'][$key] }} {{ strtolower($label) }}</span>
                        @endif
                        @endforeach
                    </span>
                    <i id="{{ $id }}-chev" class="{{ $open ? 'mgc_up_line' : 'mgc_down_line' }} text-xl text-gray-400"></i>
                </button>

                <div id="{{ $id }}-body" class="{{ $open ? '' : 'hidden' }} border-t border-gray-100 dark:border-gray-700 p-5">
                    @if($r['summary'])
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">{{ $r['summary'] }}</p>
                    @endif
                    @foreach(collect($r['changes'])->groupBy('module') as $module => $items)
                    <div class="module-group mb-4 last:mb-0" data-module="{{ $module }}">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">{{ $module }}</p>
                        <ul class="space-y-2">
                            @foreach($items as $c)
                            @php [$tLabel, $tPill, $tDot, $tIcon] = $types[$c['type']] ?? $types['improved']; @endphp
                            <li class="change flex items-start gap-3" data-type="{{ $c['type'] }}" data-module="{{ $module }}">
                                <span class="inline-flex items-center gap-1.5 py-0.5 px-2 rounded-full text-[11px] font-medium shrink-0 w-[84px] justify-center mt-0.5 {{ $tPill }}">
                                    <span class="w-1.5 h-1.5 inline-block {{ $tDot }} rounded-full"></span>{{ $tLabel }}
                                </span>
                                <span class="text-sm text-gray-700 dark:text-gray-200">{{ $c['text'] }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach

            <div id="no-matches" class="hidden card py-12 text-center text-gray-400">
                <i class="mgc_search_line text-4xl mb-2 block opacity-30"></i>No changes match these filters.
            </div>
        </div>

        <p class="text-xs text-gray-400">Versions before 3.0 are summaries rebuilt from the system's change history.</p>
    </div>
</div>
@endsection

@push('inline-scripts')
<script>
function toggleAccordion(bodyId, chevronId) {
    const opening = document.getElementById(bodyId).classList.contains('hidden');
    document.getElementById(bodyId).classList.toggle('hidden');
    document.getElementById(chevronId).classList.toggle('mgc_down_line', !opening);
    document.getElementById(chevronId).classList.toggle('mgc_up_line', opening);
    document.getElementById(chevronId).closest('button').setAttribute('aria-expanded', opening ? 'true' : 'false');
}

// Seen this version: the "New" badge in the Settings menu goes away.
try {
    localStorage.setItem('updatesSeen', @json(config('app.version')));
    document.querySelectorAll('.updates-new-dot').forEach(d => d.classList.add('hidden'));
} catch (e) {}

// ── Filters (type, module, text) ──
let typeFilter = '';
function applyFilters() {
    const mod = document.getElementById('module-filter').value;
    const q = document.getElementById('text-filter').value.trim().toLowerCase();
    const filtering = typeFilter || mod || q;
    let any = false;
    document.querySelectorAll('.release').forEach(rel => {
        let shown = 0;
        rel.querySelectorAll('.change').forEach(li => {
            const ok = (!typeFilter || li.dataset.type === typeFilter)
                    && (!mod || li.dataset.module === mod)
                    && (!q || li.textContent.toLowerCase().includes(q));
            li.classList.toggle('hidden', !ok);
            if (ok) shown++;
        });
        rel.querySelectorAll('.module-group').forEach(g => g.classList.toggle('hidden', !g.querySelector('.change:not(.hidden)')));
        rel.classList.toggle('hidden', !shown);
        // While filtering, open every release that has matches so nothing is hidden behind a fold.
        if (filtering && shown) {
            const body = rel.querySelector('[id$="-body"]');
            if (body.classList.contains('hidden')) toggleAccordion(body.id, body.id.replace('-body', '-chev'));
        }
        if (shown) any = true;
    });
    document.getElementById('no-matches').classList.toggle('hidden', any);
}
document.querySelectorAll('.type-filter').forEach(b => b.addEventListener('click', () => {
    typeFilter = b.dataset.typeFilter;
    document.querySelectorAll('.type-filter').forEach(x => {
        const on = x === b;
        x.classList.toggle('bg-primary', on); x.classList.toggle('text-white', on);
        x.classList.toggle('text-gray-600', !on); x.classList.toggle('dark:text-gray-300', !on);
    });
    applyFilters();
}));
document.getElementById('module-filter').addEventListener('change', applyFilters);
document.getElementById('text-filter').addEventListener('input', applyFilters);
</script>
@endpush
