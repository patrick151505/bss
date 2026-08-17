@extends('layouts.vertical', ['title' => 'Document Requests', 'sub_title' => 'Documents', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

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

{{-- Stat Strip --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="card px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
            <i class="mgc_inbox_line text-primary text-lg"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Total</p>
            <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats['total']) }}</p>
        </div>
    </div>
    <div class="card px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center shrink-0">
            <i class="mgc_time_line text-warning text-lg"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Pending</p>
            <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats['pending']) }}</p>
        </div>
    </div>
    <div class="card px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center shrink-0">
            <i class="mgc_send_plane_line text-warning text-lg"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Ready for Release</p>
            <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats['approved']) }}</p>
        </div>
    </div>
    <div class="card px-5 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center shrink-0">
            <i class="mgc_check_circle_line text-success text-lg"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Released</p>
            <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats['released']) }}</p>
        </div>
    </div>
</div>

{{-- Filter + Actions --}}
<div class="card mb-5">
    <div class="card-header flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('documents.requests.index') }}"
              class="flex flex-wrap items-end gap-3 flex-1">
            <div class="flex-1 min-w-[160px]">
                <label class="form-label text-xs mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Name, control no., or address…" class="form-input text-sm py-1.5">
            </div>
            <div class="w-48">
                <label class="form-label text-xs mb-1">Document Type</label>
                <select name="type" class="form-select text-sm py-1.5">
                    <option value="">All Types</option>
                    @foreach($types as $t)
                    <option value="{{ $t->id }}" {{ request('type') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-48">
                <label class="form-label text-xs mb-1">Purpose</label>
                <select name="purpose" class="form-select text-sm py-1.5">
                    <option value="">All Purposes</option>
                    @foreach($purposes as $p)
                    <option value="{{ $p }}" {{ request('purpose') === $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="form-label text-xs mb-1">Status</label>
                <select name="status" class="form-select text-sm py-1.5">
                    <option value="">All</option>
                    <option value="pending"           {{ request('status') === 'pending'           ? 'selected' : '' }}>Pending</option>
                    <option value="approved"          {{ request('status') === 'approved'          ? 'selected' : '' }}>Approved</option>
                    <option value="ready_for_release" {{ request('status') === 'ready_for_release' ? 'selected' : '' }}>Ready for Release</option>
                    <option value="released"          {{ request('status') === 'released'          ? 'selected' : '' }}>Released</option>
                    <option value="rejected"          {{ request('status') === 'rejected'          ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn bg-primary text-white text-sm py-1.5 px-4">
                    <i class="mgc_search_line me-1"></i> Filter
                </button>
                @if(request()->hasAny(['search','type','purpose','status']))
                <a href="{{ route('documents.requests.index') }}"
                   class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-sm py-1.5 px-3">Clear</a>
                @endif
            </div>
        </form>
        <div class="flex items-center gap-2 shrink-0">
            {{-- View toggle (list / grid) --}}
            <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <button type="button" id="view-list-btn" onclick="setRequestsView('list')"
                        class="px-2.5 py-2 text-sm text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800" title="List view">
                    <i class="mgc_list_check_line"></i>
                </button>
                <button type="button" id="view-grid-btn" onclick="setRequestsView('grid')"
                        class="px-2.5 py-2 text-sm text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800 border-l border-gray-200 dark:border-gray-700" title="Grid view">
                    <i class="mgc_grid_line"></i>
                </button>
            </div>
            <a href="{{ route('documents.requests.create') }}"
               class="btn bg-success text-white flex items-center gap-2">
                <i class="mgc_add_line"></i> New Request
            </a>
        </div>
    </div>

    {{-- ── List view (table) ── hidden by default; JS shows it if that's the saved view --}}
    <div id="requests-list-view" class="hidden overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-32">Control No.</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Citizen</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Document</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-40">Purpose</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-28">Fee</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-28">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-36">Requested</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-28">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($requests as $req)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3">
                        @if($req->control_number)
                        <span class="text-xs font-mono font-semibold text-gray-700 dark:text-gray-200">{{ $req->control_number }}</span>
                        @else
                        <span class="text-xs font-mono text-gray-400">{{ str_pad($req->id, 4, '0', STR_PAD_LEFT) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $req->citizen->full_name ?? '—' }}</p>
                        <p class="text-xs text-gray-400">{{ $req->citizen->complete_address ?? '' }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $req->documentType->name ?? '—' }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $req->purpose ?: '—' }}</p>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($req->is_paid)
                            <div class="text-xs font-mono font-semibold text-warning">₱ {{ number_format($req->fee, 2) }}</div>
                            @if($req->fee_paid)
                                <span class="text-[10px] text-success">✓ Paid</span>
                                @if($req->amount_paid !== null)
                                    @php $change = (float) $req->amount_paid - (float) $req->fee; @endphp
                                    <span class="text-[10px] text-gray-500 block">Paid ₱ {{ number_format($req->amount_paid, 2) }}</span>
                                    @if($change > 0)
                                        <span class="text-[10px] text-gray-400 block">Change ₱ {{ number_format($change, 2) }}</span>
                                    @elseif($change < 0)
                                        <span class="text-[10px] text-danger block">Short ₱ {{ number_format(abs($change), 2) }}</span>
                                    @endif
                                @endif
                                @if($req->or_number)
                                <span class="text-[10px] text-gray-400 block truncate max-w-[120px] mx-auto" title="{{ $req->or_number }}">
                                    {{ Str::startsWith($req->or_number, 'NO OR:') ? $req->or_number : 'OR# ' . $req->or_number }}
                                </span>
                                @endif
                            @else
                                <span class="text-[10px] text-warning">Unpaid</span>
                            @endif
                        @else
                            <span class="text-xs font-semibold text-success">FREE</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @php
                            $badges = [
                                'pending'           => ['pill' => 'bg-yellow-100 text-yellow-800', 'label' => 'Pending'],
                                'approved'          => ['pill' => 'bg-sky-100 text-sky-800',       'label' => 'Approved'],
                                'ready_for_release' => ['pill' => 'bg-yellow-100 text-yellow-800', 'label' => 'Ready for Release'],
                                'released'          => ['pill' => 'bg-green-100 text-green-800',   'label' => 'Released'],
                                'rejected'          => ['pill' => 'bg-red-100 text-red-800',       'label' => 'Rejected'],
                            ];
                            $b = $badges[$req->status] ?? ['pill' => 'bg-gray-100 text-gray-700', 'label' => ucfirst(str_replace('_', ' ', $req->status))];
                        @endphp
                        <span class="inline-flex items-center py-1 px-2.5 rounded-full text-xs font-medium whitespace-nowrap {{ $b['pill'] }}">
                            {{ $b['label'] }}
                        </span>
                        @if($req->print_count > 0)
                        <span class="mt-1 flex items-center justify-center gap-1 text-[10px] text-gray-400" title="Printed {{ $req->print_count }} time(s)">
                            <i class="mgc_print_line"></i> {{ $req->print_count }}×
                        </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                        <div class="text-gray-700 dark:text-gray-300 cursor-help"
                             title="{{ $req->created_at->format('M d, Y g:i A') }}">
                            {{ $req->created_at->diffForHumans() }}
                        </div>
                        @if($req->createdBy)
                        <div class="text-[11px] text-gray-400 flex items-center gap-1">
                            <i class="mgc_user_3_line text-[11px]"></i> {{ $req->createdBy->name }}
                        </div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('documents.requests.show', $req) }}"
                               class="text-primary hover:text-primary/70 text-sm" title="View / Print">
                                <i class="mgc_eye_2_line"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center text-gray-400 text-sm">
                        <i class="mgc_inbox_line text-3xl block mb-2"></i>
                        No document requests found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Grid view (cards) ── --}}
    <div id="requests-grid-view" class="hidden p-4">
        @php
            $gridBadges = [
                'pending'           => 'bg-yellow-100 text-yellow-800',
                'approved'          => 'bg-sky-100 text-sky-800',
                'ready_for_release' => 'bg-yellow-100 text-yellow-800',
                'released'          => 'bg-green-100 text-green-800',
                'rejected'          => 'bg-red-100 text-red-800',
            ];
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($requests as $req)
            @php
                $pill  = $gridBadges[$req->status] ?? 'bg-gray-100 text-gray-700';
                $label = ucfirst(str_replace('_', ' ', $req->status));
            @endphp
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition flex flex-col gap-3">
                {{-- Header: control no. + status on one aligned row --}}
                <div class="flex items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-1.5 text-xs font-mono font-semibold text-primary bg-primary/10 rounded px-2 py-1">
                        <i class="mgc_hashtag_line text-[11px]"></i>{{ $req->control_number ?: str_pad($req->id, 4, '0', STR_PAD_LEFT) }}
                    </span>
                    <span class="inline-flex items-center py-1 px-2.5 rounded-full text-[11px] font-medium whitespace-nowrap {{ $pill }}">
                        {{ $label }}
                    </span>
                </div>

                {{-- Citizen (the card's main identity) --}}
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0 text-gray-400">
                        <i class="mgc_user_3_line"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $req->citizen->full_name ?? '—' }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $req->citizen->complete_address ?? '' }}</p>
                    </div>
                </div>

                <div class="text-xs space-y-1.5 border-t border-gray-100 dark:border-gray-800 pt-2.5">
                    <div class="flex justify-between gap-3">
                        <span class="text-gray-400 shrink-0">Document</span>
                        <span class="text-gray-700 dark:text-gray-200 text-right truncate" title="{{ $req->documentType->name }}">{{ $req->documentType->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-gray-400 shrink-0">Purpose</span>
                        <span class="text-gray-700 dark:text-gray-200 text-right truncate" title="{{ $req->purpose }}">{{ $req->purpose ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-gray-400 shrink-0">Fee</span>
                        <span class="text-right font-medium">
                            @if($req->is_paid)
                                <span class="text-warning">₱ {{ number_format($req->fee, 2) }}</span>
                                @if($req->fee_paid)<span class="text-success text-[10px]">✓ Paid</span>@else<span class="text-warning text-[10px]">Unpaid</span>@endif
                            @else
                                <span class="text-success">FREE</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-gray-400 shrink-0">Requested</span>
                        <span class="text-gray-500 text-right truncate cursor-help" title="{{ $req->created_at->format('M d, Y g:i A') }}">
                            {{ $req->created_at->diffForHumans() }}
                        </span>
                    </div>
                    @if($req->createdBy)
                    <div class="flex justify-between gap-3">
                        <span class="text-gray-400 shrink-0">By</span>
                        <span class="text-gray-500 text-right truncate">{{ $req->createdBy->name }}</span>
                    </div>
                    @endif
                    @if($req->print_count > 0)
                    <div class="flex justify-between gap-3">
                        <span class="text-gray-400 shrink-0">Printed</span>
                        <span class="text-gray-700 dark:text-gray-200 text-right flex items-center justify-end gap-1">
                            <i class="mgc_print_line text-[11px]"></i> {{ $req->print_count }}×
                        </span>
                    </div>
                    @endif
                </div>

                <div class="flex items-center gap-2 pt-1 mt-auto">
                    <a href="{{ route('documents.requests.show', $req) }}"
                       class="btn btn-sm bg-primary/10 text-primary flex-1 flex items-center justify-center gap-1">
                        <i class="mgc_eye_2_line"></i> View
                    </a>
                </div>
            </div>
            @empty
            <div class="col-span-full py-12 text-center text-gray-400 text-sm">
                <i class="mgc_inbox_line text-3xl block mb-2"></i>
                No document requests found.
            </div>
            @endforelse
        </div>
    </div>

    @if($requests->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
        {{ $requests->links() }}
    </div>
    @endif
</div>

@push('inline-scripts')
<script>
function setRequestsView(view) {
    const list = document.getElementById('requests-list-view');
    const grid = document.getElementById('requests-grid-view');
    const lb   = document.getElementById('view-list-btn');
    const gb   = document.getElementById('view-grid-btn');
    const isGrid = view === 'grid';

    list.classList.toggle('hidden', isGrid);
    grid.classList.toggle('hidden', !isGrid);

    // Active-state styling on the toggle buttons.
    [[lb, !isGrid], [gb, isGrid]].forEach(([btn, active]) => {
        btn.classList.toggle('bg-primary', active);
        btn.classList.toggle('text-white', active);
        btn.classList.toggle('text-gray-500', !active);
    });

    localStorage.setItem('requestsView', view);
}

// Restore the saved view (default: grid).
setRequestsView(localStorage.getItem('requestsView') || 'grid');
</script>
@endpush

@endsection
