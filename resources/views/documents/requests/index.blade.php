@extends('layouts.vertical', [
    'title'         => 'Document Requests',
    'sub_title'     => 'Documents',
    'sub_title_url' => route('documents.requests.index'),
    'tagline'       => 'Issue, approve and release barangay certificates.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@php
    $peso = fn($v) => '₱ ' . number_format($v, 2);
    $range     = $rangeKey;
    $rangeOpts = ['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'all' => 'All time', 'custom' => 'Custom'];
    $status    = request('status');

    // Status → [label, pill classes, dot class]
    $statusMeta = [
        'pending'           => ['Pending',           'bg-yellow-100 text-yellow-800', 'bg-yellow-400'],
        'approved'          => ['Approved',          'bg-sky-100 text-sky-800',       'bg-sky-400'],
        'ready_for_release' => ['Ready for release', 'bg-indigo-100 text-indigo-800', 'bg-indigo-400'],
        'released'          => ['Released',          'bg-green-100 text-green-800',   'bg-green-400'],
        'rejected'          => ['Rejected',          'bg-red-100 text-red-800',       'bg-red-400'],
        'voided'            => ['Voided',            'bg-gray-200 text-gray-700',     'bg-gray-500'],
    ];

    // One payment pill per request: [label, classes, tooltip]
    $payment = function ($req) use ($peso) {
        if (! $req->is_paid) {
            return ['Free', 'bg-gray-100 text-gray-700', 'No fee for this document'];
        }
        $receipt = $req->is_no_or ? 'No OR — ' . $req->no_or_reason : ($req->or_number ? 'OR# ' . $req->or_number : null);
        return match ($req->payment_status) {
            'paid'   => ['Paid', 'bg-green-100 text-green-800',
                         collect([$receipt, $req->change_amount > 0 ? 'Change ' . $peso($req->change_amount) : null])->filter()->implode(' · ')],
            'short'  => ['Short ' . $peso($req->short_amount), 'bg-red-100 text-red-800',
                         'Received ' . $peso($req->amount_paid) . ' of ' . $peso($req->fee)],
            'voided' => ['Voided', 'bg-gray-200 text-gray-700', 'Excluded from fee totals'],
            default  => ['Unpaid', 'bg-yellow-100 text-yellow-800', 'Paid at release'],
        };
    };

    $initials = fn($name) => collect(explode(' ', (string) $name))->filter()->map(fn($s) => mb_strtoupper(mb_substr($s, 0, 1)))->take(2)->implode('') ?: '?';

    $hasFilters   = collect(request()->only(['search', 'type', 'purpose', 'status']))->filter(fn($v) => filled($v))->isNotEmpty();
    $collectedPct = $stats['expected'] > 0 ? min(100, round($stats['collected'] / $stats['expected'] * 100)) : 0;
    $periodText   = $rangeLabel ?? 'All time';
    $canFees      = auth()->user()->can('documents.fees');
@endphp

@section('content')

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

{{-- ── Period + main actions ── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="flex flex-wrap items-center gap-2">
        <div class="inline-flex max-w-full overflow-x-auto [scrollbar-width:none] rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-1 gap-1" role="radiogroup" aria-label="Date requested">
            @foreach($rangeOpts as $val => $label)
            <label class="cursor-pointer">
                <input type="radio" name="range" value="{{ $val }}" form="requests-filter" class="peer sr-only"
                       {{ $range === $val ? 'checked' : '' }} onchange="onRangeChange(this.value)">
                <span class="block whitespace-nowrap px-3 py-1.5 rounded-md text-sm font-medium transition text-gray-600 dark:text-gray-300
                             hover:bg-gray-100 dark:hover:bg-gray-700
                             peer-checked:bg-primary peer-checked:text-white peer-checked:hover:bg-primary
                             peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40">{{ $label }}</span>
            </label>
            @endforeach
        </div>
        <div id="custom-range" class="{{ $range === 'custom' ? 'flex' : 'hidden' }} flex-wrap items-center gap-2">
            <input type="date" name="from" form="requests-filter" value="{{ request('from') }}" class="form-input text-sm py-1.5 w-auto" aria-label="From date">
            <span class="text-xs text-gray-400">to</span>
            <input type="date" name="to" form="requests-filter" value="{{ request('to') }}" class="form-input text-sm py-1.5 w-auto" aria-label="To date">
            <button type="submit" form="requests-filter" class="btn btn-sm bg-primary text-white">Apply</button>
        </div>
        @if($rangeLabel && $range !== 'custom' && preg_match('/\((.+)\)/', $rangeLabel, $m))
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $m[1] }}</span>
        @endif
    </div>
    <div class="flex items-center gap-2">
        @if($canFees)
        <button type="button" onclick="toggleFeeSummary()" id="fee-summary-btn"
                class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white" aria-expanded="false" aria-controls="fee-summary-body">
            <i class="mgc_wallet_3_line me-1"></i>Fee summary <i id="fee-summary-chevron" class="mgc_down_line ms-1"></i>
        </button>
        @endif
        @can('documents.create')
        <a href="{{ route('documents.requests.create') }}" class="btn bg-primary text-white">
            <i class="mgc_add_line me-1"></i>New Request
        </a>
        @endcan
    </div>
</div>

{{-- ── Summary strip — follows every filter ── --}}
<div class="card mb-6">
    <div class="grid grid-cols-2 {{ $canFees ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} divide-y lg:divide-y-0 lg:divide-x divide-gray-100 dark:divide-gray-700">
        @foreach([
            ['Requests',          $stats['filtered'], 'mgc_inbox_line',        'text-primary', $stats['is_filtered'] ? 'of ' . number_format($stats['all']) . ' total' : 'all requests'],
            ['Pending',           $stats['pending'],  'mgc_time_line',         'text-warning', 'waiting for approval'],
            ['Ready for release', $stats['approved'], 'mgc_send_plane_line',   'text-info',    'approved, to hand over'],
            ['Released',          $stats['released'], 'mgc_check_circle_line', 'text-success', 'handed to citizen'],
        ] as $i => [$label, $value, $icon, $fg, $sub])
        <div class="p-5 {{ $i % 2 === 1 ? 'border-l lg:border-l-0 border-gray-100 dark:border-gray-700' : '' }}">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide flex items-center gap-1.5"><i class="{{ $icon }} {{ $fg }} text-base"></i>{{ $label }}</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100 mt-1">{{ number_format($value) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $sub }}</p>
        </div>
        @endforeach

        @if($canFees)
        <div class="p-5 col-span-2 lg:col-span-1">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide flex items-center gap-1.5"><i class="mgc_wallet_3_line text-success text-base"></i>Fees collected</p>
                <span class="text-xs font-semibold {{ $collectedPct >= 100 ? 'text-success' : 'text-gray-500 dark:text-gray-400' }}">{{ $collectedPct }}%</span>
            </div>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100 mt-1">{{ $peso($stats['collected']) }}</p>
            <div class="w-full h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 mt-2 overflow-hidden">
                <div class="h-full rounded-full bg-success" style="width: {{ $collectedPct }}%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-1.5">
                of {{ $peso($stats['expected']) }} possible
                @if($stats['short_total'] > 0)· <span class="text-danger">{{ $peso($stats['short_total']) }} short</span>@endif
            </p>
        </div>
        @endif
    </div>
</div>

{{-- ── Fee summary by document type (toggled; documents.fees only) ── --}}
@if($canFees)
@php
    $fs = $feeSummary;
    $ft = $fs['totals'];
    $feeText = fn($r) => $r['fee_min'] == $r['fee_max'] ? $peso($r['fee_min']) : $peso($r['fee_min']) . ' – ' . $peso($r['fee_max']);
@endphp
<div id="fee-summary-body" class="hidden card mb-6">
    <div class="card-header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <div>
                <h4 class="card-title">Fee Summary</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Possible income by document type · {{ $periodText }}</p>
            </div>
            <a href="{{ route('documents.requests.fee-summary', request()->query()) }}" target="_blank"
               class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white text-sm">
                <i class="mgc_print_line me-1"></i>Print
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 px-6 pt-5">
        @foreach([
            ['Possible income',  $ft['possible'],  'text-gray-800 dark:text-gray-100', 'If every request is released and paid in full'],
            ['Collected',        $ft['collected'], 'text-success',                     'Received on released documents'],
            ['Awaiting release', $ft['awaiting'],  'text-warning',                     'Pending, approved or ready — not yet paid'],
            ['Short',            $ft['short'],     $ft['short'] > 0 ? 'text-danger' : 'text-gray-800 dark:text-gray-100', 'Released with less than the fee'],
        ] as [$label, $value, $tone, $hint])
        <div class="p-4 rounded-lg border border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $label }}</p>
            <p class="text-lg font-bold {{ $tone }} mt-0.5">{{ $peso($value) }}</p>
            <p class="text-[11px] text-gray-400 mt-0.5">{{ $hint }}</p>
        </div>
        @endforeach
    </div>

    <div class="overflow-x-auto mt-5">
        <table class="table min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    @foreach(['Document type' => 'left', 'Fee' => 'right', 'Requests' => 'right', 'Released' => 'right', 'Possible income' => 'right', 'Collected' => 'right', 'Awaiting' => 'right', 'Short' => 'right'] as $h => $align)
                    <th class="px-6 py-3 text-{{ $align }} text-xs font-medium text-gray-500 uppercase dark:text-gray-400 whitespace-nowrap">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($fs['rows'] as $r)
                <tr>
                    <td class="px-5 py-3 text-gray-800 dark:text-gray-200">
                        <span class="inline-flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $r['color'] }};"></span>{{ $r['name'] }}</span>
                    </td>
                    <td class="px-5 py-3 text-right whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $feeText($r) }}</td>
                    <td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($r['requests']) }}</td>
                    <td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($r['released']) }}</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap font-semibold text-gray-800 dark:text-gray-100">{{ $peso($r['possible']) }}</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap {{ $r['collected'] > 0 ? 'text-success' : 'text-gray-400' }}">{{ $peso($r['collected']) }}</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap {{ $r['awaiting'] > 0 ? 'text-warning' : 'text-gray-400' }}">{{ $peso($r['awaiting']) }}</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap {{ $r['short'] > 0 ? 'text-danger' : 'text-gray-400' }}">{{ $r['short'] > 0 ? $peso($r['short']) : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-12 text-gray-400">
                    <i class="mgc_wallet_3_line text-4xl mb-2 block opacity-30"></i>No paid document requests for these filters.
                </td></tr>
                @endforelse
            </tbody>
            @if($fs['rows']->isNotEmpty())
            <tfoot class="bg-gray-50 dark:bg-gray-700/50 font-semibold">
                <tr>
                    <td class="px-5 py-3 text-gray-800 dark:text-gray-100" colspan="2">Total</td>
                    <td class="px-5 py-3 text-right text-gray-800 dark:text-gray-100">{{ number_format($ft['requests']) }}</td>
                    <td class="px-5 py-3 text-right text-gray-800 dark:text-gray-100">{{ number_format($ft['released']) }}</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap text-gray-800 dark:text-gray-100">{{ $peso($ft['possible']) }}</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap text-success">{{ $peso($ft['collected']) }}</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap text-warning">{{ $peso($ft['awaiting']) }}</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap {{ $ft['short'] > 0 ? 'text-danger' : 'text-gray-400' }}">{{ $ft['short'] > 0 ? $peso($ft['short']) : '—' }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    @if($fs['free'] || $fs['excluded'])
    <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 flex flex-wrap gap-x-5 gap-y-1">
        @if($fs['free'])<span><i class="mgc_gift_line"></i> {{ number_format($fs['free']) }} free document{{ $fs['free'] > 1 ? 's' : '' }} (no fee)</span>@endif
        @if($fs['excluded'])<span><i class="mgc_close_circle_line"></i> {{ number_format($fs['excluded']) }} rejected/voided ({{ $peso($fs['excluded_amount']) }}) not counted</span>@endif
    </div>
    @endif
</div>
@endif

{{-- ── Requests ── --}}
<div class="card">
    {{-- Status tabs (counts ignore the status filter itself) --}}
    <div class="border-b border-gray-100 dark:border-gray-700 overflow-x-auto overflow-y-hidden [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        <nav class="flex gap-1 px-4 min-w-max" aria-label="Filter by status">
            @php
                $tabs = ['' => ['All', $stats['status_all']]];
                foreach ($statusMeta as $key => [$label]) {
                    $n = $stats['by_status'][$key];
                    // Rarely-used statuses only show up when they have requests (or are selected).
                    if ($n > 0 || $status === $key || in_array($key, ['pending', 'ready_for_release', 'released'])) {
                        $tabs[$key] = [$label, $n];
                    }
                }
            @endphp
            @foreach($tabs as $key => [$label, $n])
            @php $active = (string) $status === (string) $key; @endphp
            <a href="{{ request()->fullUrlWithQuery(['status' => $key ?: null, 'page' => null]) }}"
               class="flex items-center gap-2 px-3 py-3.5 text-sm font-medium border-b-2 -mb-px whitespace-nowrap transition
                      {{ $active ? 'border-primary text-primary' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">
                @if($key)<span class="w-1.5 h-1.5 rounded-full {{ $statusMeta[$key][2] }}"></span>@endif
                {{ $label }}
                <span class="text-xs px-1.5 py-0.5 rounded-full {{ $active ? 'bg-primary/10 text-primary' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">{{ number_format($n) }}</span>
            </a>
            @endforeach
        </nav>
    </div>

    {{-- Search + filters --}}
    <form id="requests-filter" method="GET" action="{{ route('documents.requests.index') }}" class="p-4 flex flex-wrap items-center gap-2">
        @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        <div class="relative flex-1 min-w-[200px]">
            <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="search" name="search" value="{{ request('search') }}"
                   placeholder="Search name, control no. or address…" class="form-input text-sm ps-9 w-full">
        </div>
        <select name="type" class="form-select text-sm w-full sm:w-48" onchange="this.form.requestSubmit()" aria-label="Document type">
            <option value="">All documents</option>
            @foreach($types as $t)
            <option value="{{ $t->id }}" @selected(request('type') == $t->id)>{{ $t->name }}</option>
            @endforeach
        </select>
        <select name="purpose" class="form-select text-sm w-full sm:w-44" onchange="this.form.requestSubmit()" aria-label="Purpose">
            <option value="">All purposes</option>
            @foreach($purposes as $p)
            <option value="{{ $p }}" @selected(request('purpose') === $p)>{{ $p }}</option>
            @endforeach
        </select>
        <select name="source" class="form-select text-sm w-full sm:w-36" onchange="this.form.requestSubmit()" aria-label="Where requested">
            <option value="">Counter &amp; kiosk</option>
            <option value="kiosk" @selected(request('source') === 'kiosk')>From kiosk</option>
            <option value="counter" @selected(request('source') === 'counter')>At the counter</option>
        </select>
        <button type="submit" class="btn bg-primary text-white"><i class="mgc_search_line me-1"></i>Search</button>
        <div class="hidden md:inline-flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden ms-auto">
            <button type="button" id="view-list-btn" onclick="setRequestsView('list')"
                    class="px-3 py-2 text-sm text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700" title="List view" aria-label="List view">
                <i class="mgc_list_check_line"></i>
            </button>
            <button type="button" id="view-grid-btn" onclick="setRequestsView('grid')"
                    class="px-3 py-2 text-sm text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 border-l border-gray-200 dark:border-gray-700" title="Card view" aria-label="Card view">
                <i class="mgc_grid_line"></i>
            </button>
        </div>
    </form>

    {{-- Active filters as removable chips --}}
    @php
        $chips = collect([
            'search'  => filled(request('search')) ? '“' . request('search') . '”' : null,
            'type'    => filled(request('type')) ? $types->firstWhere('id', request('type'))?->name : null,
            'purpose' => request('purpose'),
            'status'  => $status ? ($statusMeta[$status][0] ?? $status) : null,
        ])->filter(fn($v) => filled($v));
    @endphp
    @if($chips->isNotEmpty())
    <div class="px-4 pb-3 -mt-1 flex flex-wrap items-center gap-2">
        @foreach($chips as $key => $label)
        <a href="{{ request()->fullUrlWithQuery([$key => null, 'page' => null]) }}"
           class="inline-flex items-center gap-1 py-1 px-2.5 rounded-full text-xs font-medium bg-primary/10 text-primary hover:bg-primary/25" title="Remove filter">
            {{ $label }} <i class="mgc_close_line"></i>
        </a>
        @endforeach
        <a href="{{ route('documents.requests.index', ['range' => $range !== 'today' ? $range : null, 'from' => request('from'), 'to' => request('to')]) }}"
           class="text-xs text-gray-500 dark:text-gray-400 hover:text-danger">Clear all</a>
    </div>
    @endif

    {{-- ── List view ── --}}
    <div id="requests-list-view" class="hidden overflow-x-auto border-t border-gray-100 dark:border-gray-700">
        <table class="table min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Request</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Citizen</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Document</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Fee</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                    <th class="px-5 py-3"><span class="sr-only">Open</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($requests as $req)
                @php
                    [$sLabel, $sPill, $sDot] = $statusMeta[$req->status] ?? [ucfirst(str_replace('_', ' ', $req->status)), 'bg-gray-100 text-gray-700', 'bg-gray-400'];
                    [$pLabel, $pPill, $pTip] = $payment($req);
                    $url = route('documents.requests.show', $req);
                @endphp
                <tr class="group hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer" onclick="if (!event.target.closest('a')) location.href='{{ $url }}'">
                    <td class="px-5 py-3 whitespace-nowrap">
                        <a href="{{ $url }}" class="font-mono text-xs font-semibold text-gray-800 dark:text-gray-100 group-hover:text-primary">{{ $req->control_number ?: '#' . str_pad($req->id, 4, '0', STR_PAD_LEFT) }}</a>@if($req->queue_no)<span class="ms-1 inline-flex items-center gap-1 py-0.5 px-1.5 rounded text-[10px] font-semibold bg-info/15 text-info" title="Requested at the kiosk"><i class="mgc_computer_line"></i>{{ $req->queue_no }}</span>@endif
                        <p class="text-xs text-gray-400 mt-0.5" title="{{ $req->createdBy ? 'By ' . $req->createdBy->name : '' }}">{{ $req->created_at->format('M d, Y g:i A') }}</p>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3 min-w-[200px]">
                            @if($req->citizen?->profile_url)
                            <img src="{{ $req->citizen->profile_url }}" alt="" class="w-9 h-9 rounded-full object-cover shrink-0" onerror="this.nextElementSibling.classList.replace('hidden', 'flex'); this.remove()">
                            <span class="hidden w-9 h-9 rounded-full bg-primary/10 text-primary text-xs font-semibold items-center justify-center shrink-0">{{ $initials($req->citizen?->full_name) }}</span>
                            @else
                            <span class="w-9 h-9 rounded-full bg-primary/10 text-primary text-xs font-semibold flex items-center justify-center shrink-0">{{ $initials($req->citizen?->full_name) }}</span>
                            @endif
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 dark:text-gray-100 truncate max-w-[240px]">
                                    {{ $req->citizen->full_name ?? '—' }}
                                    @if($req->citizen?->is_walkin)<span class="ms-1 inline-flex items-center gap-1 py-0.5 px-1.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-700 align-middle">Walk-in</span>@endif
                                </p>
                                <p class="text-xs text-gray-400 truncate max-w-[240px]">{{ $req->citizen?->full_address }}</p>
                                @if($req->careOf)
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[240px]"><i class="mgc_user_follow_line text-warning"></i> c/o {{ $req->careOf->full_name }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3">
                        <p class="flex items-center gap-2 text-gray-800 dark:text-gray-100 whitespace-nowrap">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $req->documentType->badge_color ?? '#9ca3af' }};"></span>
                            {{ $req->documentType->name ?? '—' }}
                        </p>
                        @if($req->purpose)<p class="text-xs text-gray-400 ps-[18px] truncate max-w-[220px]">{{ $req->purpose }}</p>@endif
                    </td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        @if($req->is_paid)<p class="font-medium text-gray-800 dark:text-gray-100">{{ $peso($req->fee) }}</p>@endif
                        <span class="inline-flex mt-0.5 py-0.5 px-2 rounded-full text-[11px] font-medium {{ $pPill }}" @if($pTip) title="{{ $pTip }}" @endif>{{ $pLabel }}</span>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium {{ $sPill }}">
                            <span class="w-1.5 h-1.5 inline-block {{ $sDot }} rounded-full"></span>{{ $sLabel }}
                        </span>
                        @if($req->print_count > 0)
                        <p class="text-[11px] text-gray-400 mt-1 flex items-center gap-1"><i class="mgc_print_line"></i>Printed {{ $req->print_count }}×</p>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ $url }}" class="inline-flex w-8 h-8 items-center justify-center rounded-full text-gray-400 group-hover:text-primary group-hover:bg-primary/10" title="Open">
                            <i class="mgc_right_line text-lg"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── Card view ── --}}
    <div id="requests-grid-view" class="hidden p-4 border-t border-gray-100 dark:border-gray-700">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($requests as $req)
            @php
                [$sLabel, $sPill, $sDot] = $statusMeta[$req->status] ?? [ucfirst(str_replace('_', ' ', $req->status)), 'bg-gray-100 text-gray-700', 'bg-gray-400'];
                [$pLabel, $pPill, $pTip] = $payment($req);
            @endphp
            <a href="{{ route('documents.requests.show', $req) }}"
               class="group rounded-xl border border-gray-200 dark:border-gray-700 border-l-4 p-4 flex flex-col gap-3 hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 transition"
               style="border-left-color: {{ $req->documentType->badge_color ?? '#9ca3af' }};">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $req->documentType->name ?? '—' }}</p>
                        <p class="font-mono text-xs text-gray-400">{{ $req->control_number ?: '#' . str_pad($req->id, 4, '0', STR_PAD_LEFT) }}@if($req->queue_no)<span class="ms-1 inline-flex items-center gap-1 py-0.5 px-1.5 rounded text-[10px] font-semibold bg-info/15 text-info" title="Requested at the kiosk"><i class="mgc_computer_line"></i>{{ $req->queue_no }}</span>@endif</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-[11px] font-medium whitespace-nowrap {{ $sPill }}">
                        <span class="w-1.5 h-1.5 inline-block {{ $sDot }} rounded-full"></span>{{ $sLabel }}
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    @if($req->citizen?->profile_url)
                    <img src="{{ $req->citizen->profile_url }}" alt="" class="w-10 h-10 rounded-full object-cover shrink-0" onerror="this.nextElementSibling.classList.replace('hidden', 'flex'); this.remove()">
                    <span class="hidden w-10 h-10 rounded-full bg-primary/10 text-primary text-sm font-semibold items-center justify-center shrink-0">{{ $initials($req->citizen?->full_name) }}</span>
                    @else
                    <span class="w-10 h-10 rounded-full bg-primary/10 text-primary text-sm font-semibold flex items-center justify-center shrink-0">{{ $initials($req->citizen?->full_name) }}</span>
                    @endif
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $req->citizen->full_name ?? '—' }}</p>
                        <p class="text-xs text-gray-400 truncate">
                            @if($req->citizen?->is_walkin)Walk-in{{ $req->careOf ? ' c/o ' . $req->careOf->full_name : '' }} · @endif{{ $req->purpose ?: 'No purpose given' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2 pt-3 mt-auto border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-1.5">
                        @if($req->is_paid)<span class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $peso($req->fee) }}</span>@endif
                        <span class="py-0.5 px-2 rounded-full text-[11px] font-medium {{ $pPill }}" @if($pTip) title="{{ $pTip }}" @endif>{{ $pLabel }}</span>
                    </div>
                    <span class="text-xs text-gray-400 whitespace-nowrap" title="{{ $req->created_at->format('M d, Y g:i A') }}{{ $req->createdBy ? ' · by ' . $req->createdBy->name : '' }}">
                        {{ $req->created_at->isToday() ? $req->created_at->format('g:i A') : $req->created_at->format('M d, Y') }}
                    </span>
                </div>
            </a>
            @endforeach
        </div>
    </div>

    {{-- Empty state (shared by both views) --}}
    @if($requests->isEmpty())
    <div class="py-14 text-center border-t border-gray-100 dark:border-gray-700">
        <i class="mgc_inbox_line text-5xl text-gray-300 dark:text-gray-600 block mb-3"></i>
        <p class="text-gray-700 dark:text-gray-200 font-medium">
            {{ $hasFilters ? 'No requests match these filters' : 'No requests ' . strtolower($range === 'all' ? 'yet' : ($rangeOpts[$range] === 'Custom' ? 'in this period' : $rangeOpts[$range])) }}
        </p>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            @if($range !== 'all')
            <a href="{{ request()->fullUrlWithQuery(['range' => 'all', 'from' => null, 'to' => null, 'page' => null]) }}"
               class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Show all time</a>
            @endif
            @can('documents.create')
            <a href="{{ route('documents.requests.create') }}" class="btn bg-primary text-white"><i class="mgc_add_line me-1"></i>New Request</a>
            @endcan
        </div>
    </div>
    @endif

    @if($requests->total() > 0)
    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Showing {{ number_format($requests->firstItem()) }}–{{ number_format($requests->lastItem()) }} of {{ number_format($requests->total()) }} · {{ $periodText }}
        </p>
        @if($requests->hasPages()){{ $requests->links() }}@endif
    </div>
    @endif
</div>

@endsection

@push('inline-scripts')
<script>
@if($canFees)
// ── Fee summary (remembers whether it was left open) ────────────────────
function toggleFeeSummary(open) {
    const body = document.getElementById('fee-summary-body');
    open = open ?? body.classList.contains('hidden');
    body.classList.toggle('hidden', !open);
    document.getElementById('fee-summary-btn').setAttribute('aria-expanded', open ? 'true' : 'false');
    const chev = document.getElementById('fee-summary-chevron');
    chev.classList.toggle('mgc_down_line', !open);
    chev.classList.toggle('mgc_up_line', open);
    try { localStorage.setItem('feeSummaryOpen', open ? '1' : '0'); } catch (e) {}
}
try { if (document.getElementById('fee-summary-body') && localStorage.getItem('feeSummaryOpen') === '1') toggleFeeSummary(true); } catch (e) {}
@endif

// Presets apply right away; Custom reveals From/To and waits for "Apply".
function onRangeChange(value) {
    const custom = document.getElementById('custom-range');
    custom.classList.toggle('hidden', value !== 'custom');
    custom.classList.toggle('flex', value === 'custom');
    if (value === 'custom') {
        custom.querySelector('input[name="from"]').focus();
        return;
    }
    // Drop stale custom dates so they don't ride along with a preset.
    custom.querySelectorAll('input').forEach(i => i.value = '');
    document.getElementById('requests-filter').requestSubmit();
}

// Leave blank fields out of the URL (?range=month, not ?search=&type=&…).
document.getElementById('requests-filter')?.addEventListener('formdata', e => {
    for (const [key, value] of [...e.formData.entries()]) {
        if (value === '') e.formData.delete(key);
    }
});

// ── List / card view. Phones always get cards (the table is too wide). ──
const smallScreen = window.matchMedia('(max-width: 767px)');
function setRequestsView(view, remember = true) {
    const isGrid = view === 'grid' || smallScreen.matches;
    document.getElementById('requests-list-view').classList.toggle('hidden', isGrid);
    document.getElementById('requests-grid-view').classList.toggle('hidden', !isGrid);
    [['view-list-btn', !isGrid], ['view-grid-btn', isGrid]].forEach(([id, active]) => {
        const btn = document.getElementById(id);
        btn.classList.toggle('bg-primary', active);
        btn.classList.toggle('text-white', active);
        btn.classList.toggle('text-gray-500', !active);
    });
    if (remember) { try { localStorage.setItem('requestsView', view); } catch (e) {} }
}
function savedView() { try { return localStorage.getItem('requestsView') || 'list'; } catch (e) { return 'list'; } }
setRequestsView(savedView(), false);
smallScreen.addEventListener('change', () => setRequestsView(savedView(), false));
</script>
@endpush
