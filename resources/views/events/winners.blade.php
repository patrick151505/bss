@extends('layouts.vertical', [
    'title'       => 'Raffle Winners — ' . $event->title,
    'breadcrumbs' => [
        ['label' => 'Events',      'url' => route('events.index')],
        ['label' => $event->title, 'url' => route('events.show', $event)],
        ['label' => 'Winners',     'url' => ''],
    ],
    'tagline'     => 'Edit prizes, track claiming, and export the winners list.',
    'mode'        => $mode ?? '',
    'demo'        => $demo ?? '',
])

@section('content')
@php
    $canEdit = auth()->user()->can('events.edit');
    $idFmt   = \App\Models\Setting::instance();
@endphp

{{-- ── Stats ── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    @foreach([
        ['Winners',       $stats['total'],                     'mgc_trophy_line',       'bg-warning/10', 'text-warning', 'drawn in this event'],
        ['Claimed',       $stats['claimed'],                   'mgc_check_circle_line', 'bg-success/10', 'text-success', $stats['total'] ? round($stats['claimed'] / $stats['total'] * 100) . '% handed over' : '—'],
        ['Unclaimed',     $stats['total'] - $stats['claimed'], 'mgc_time_line',         'bg-danger/10',  'text-danger',  'still to hand over'],
        ['No prize set',  $stats['no_prize'],                  'mgc_gift_line',         'bg-info/10',    'text-info',    'set before exporting'],
    ] as [$label, $value, $icon, $bg, $fg, $sub])
    <div class="card">
        <div class="p-3 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl {{ $bg }} flex items-center justify-center shrink-0"><i class="{{ $icon }} text-lg {{ $fg }}"></i></div>
            <div class="min-w-0">
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $label }}</p>
                <h3 class="text-lg font-bold leading-tight text-gray-800 dark:text-gray-100">{{ number_format($value) }}</h3>
                <p class="text-xs text-gray-400 truncate">{{ $sub }}</p>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <h4 class="card-title me-2">Raffle Winners</h4>
                {{-- Quick filters (client-side) --}}
                @foreach(['all' => 'All', 'unclaimed' => 'Unclaimed', 'claimed' => 'Claimed', 'noprize' => 'No prize'] as $key => $lbl)
                    <button type="button" data-filter="{{ $key }}"
                        class="winner-filter px-3 py-1 rounded-full text-xs font-medium border transition
                               {{ $key === 'all' ? 'bg-primary text-white border-primary' : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-primary/50' }}">
                        {{ $lbl }}
                    </button>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-2">
                @if($event->raffle_enabled && auth()->user()->can('events.edit'))
                <a href="{{ route('events.raffle.page', $event) }}" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">
                    <i class="mgc_star_line me-1"></i> Raffle
                </a>
                @endif
                <a href="{{ route('events.winners.export', [$event, 'format' => 'csv']) }}" class="btn btn-sm bg-success/25 text-success hover:bg-success hover:text-white">
                    <i class="mgc_file_download_line me-1"></i> CSV
                </a>
                <a href="{{ route('events.winners.export', [$event, 'format' => 'pdf']) }}" class="btn btn-sm bg-danger/25 text-danger hover:bg-danger hover:text-white">
                    <i class="mgc_pdf_line me-1"></i> PDF
                </a>
            </div>
        </div>
    </div>

    @if($canEdit && $winners->isNotEmpty())
    {{-- ── Bulk action bar (appears when winners are ticked) ── --}}
    <div id="bulk-bar" class="hidden px-6 py-3 bg-primary/5 border-b border-primary/20 flex flex-wrap items-center gap-3">
        <span class="text-sm font-semibold text-primary"><span id="bulk-count">0</span> selected</span>
        <div class="flex items-center gap-2 flex-1 min-w-[260px]">
            <input type="text" id="bulk-prize" list="prize-suggestions" maxlength="200"
                   class="form-input text-sm py-1.5 flex-1" placeholder="Prize for selected, e.g. 5kg Bigas">
            <button type="button" onclick="bulkAction('prize')" class="btn btn-sm bg-primary text-white whitespace-nowrap">
                <i class="mgc_gift_line me-1"></i> Set prize
            </button>
        </div>
        <button type="button" onclick="bulkAction('claim')" class="btn btn-sm bg-success/25 text-success hover:bg-success hover:text-white">
            <i class="mgc_check_line me-1"></i> Mark claimed
        </button>
        <button type="button" onclick="bulkAction('unclaim')" class="btn btn-sm bg-warning/25 text-warning hover:bg-warning hover:text-white">
            <i class="mgc_back_2_line me-1"></i> Mark unclaimed
        </button>
        <button type="button" onclick="clearSelection()" class="text-xs text-gray-500 dark:text-gray-400 hover:text-danger">Clear</button>
    </div>
    <datalist id="prize-suggestions">
        @foreach($prizeSuggestions as $p)<option value="{{ $p }}">@endforeach
    </datalist>
    @endif

    <div class="overflow-x-auto">
        <table class="table min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    @if($canEdit)
                    <th class="px-4 py-3 w-10"><input type="checkbox" id="select-all" class="form-checkbox rounded text-primary" title="Select all shown"></th>
                    @endif
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-16">Round</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Winner</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Address</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 min-w-[220px]">Prize</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Drawn</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse($winners as $w)
                @php $c = $w->citizen; @endphp
                <tr class="winner-row border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40"
                    data-id="{{ $w->id }}" data-claimed="{{ $w->isClaimed() ? 1 : 0 }}" data-prize="{{ filled($w->prize_label) ? 1 : 0 }}">
                    @if($canEdit)
                    <td class="px-4 py-3"><input type="checkbox" class="row-check form-checkbox rounded text-primary" value="{{ $w->id }}"></td>
                    @endif
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-warning/10 text-warning text-xs font-bold border border-warning/20">#{{ $w->round }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full overflow-hidden bg-primary/15 text-primary flex items-center justify-center text-xs font-bold shrink-0">
                                @if($c?->profile_url)
                                    <img src="{{ $c->profile_url }}" class="w-full h-full object-cover" alt="">
                                @else
                                    {{ strtoupper(substr($c?->fname ?? '?', 0, 1) . substr($c?->lname ?? '', 0, 1)) }}
                                @endif
                            </div>
                            <div class="min-w-0">
                                @if($c)
                                <a href="{{ route('citizens.show', $c->id) }}" class="font-semibold text-gray-800 dark:text-gray-100 hover:text-primary">{{ $c->full_name }}</a>
                                <p class="text-xs font-mono text-gray-400">{{ $idFmt->formatCitizenId($c->id) }}</p>
                                @else
                                <span class="text-gray-400">—</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $c?->full_address ?: ($c?->addressZone?->description ?? '—') }}</td>
                    <td class="px-4 py-3">
                        @if($canEdit)
                        <div class="flex items-center gap-1.5">
                            <input type="text" value="{{ $w->prize_label }}" list="prize-suggestions" maxlength="200"
                                   class="prize-input form-input text-sm py-1 px-2 flex-1 min-w-0"
                                   data-url="{{ route('events.winners.prize', [$event, $w]) }}"
                                   data-saved="{{ $w->prize_label }}" placeholder="Set prize…">
                            <i class="prize-state text-base shrink-0"></i>
                        </div>
                        @else
                            {{ $w->prize_label ?: '—' }}
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                        {{ $w->drawn_at?->format('M d, Y') }}
                        <span class="block text-xs text-gray-400">{{ $w->drawn_at?->format('g:i A') }}</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($w->isClaimed())
                            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <span class="w-1.5 h-1.5 inline-block bg-green-400 rounded-full"></span>Claimed
                            </span>
                            <span class="block text-[11px] text-gray-400 mt-1">{{ $w->claimed_at->format('M d, g:i A') }}@if($w->claimedBy) · {{ $w->claimedBy->name }}@endif</span>
                        @else
                            <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <span class="w-1.5 h-1.5 inline-block bg-yellow-400 rounded-full"></span>Unclaimed
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-12 text-gray-400">
                    <i class="mgc_trophy_line text-4xl mb-2 block opacity-30"></i>No winners drawn yet.
                </td></tr>
            @endforelse
                <tr id="filter-empty" class="hidden"><td colspan="7" class="text-center py-10 text-gray-400">No winners match this filter.</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('inline-scripts')
<script>
(function () {
    const CSRF     = @json(csrf_token());
    const BULK_URL = @json(route('events.winners.bulk', $event));
    const rows     = [...document.querySelectorAll('.winner-row')];
    const swalBtn  = color => () => document.querySelector('.swal2-confirm')?.style.setProperty('background-color', color, 'important');
    const toast    = (icon, title) => Swal.fire({ toast: true, position: 'top-end', icon, title, showConfirmButton: false, timer: 1800 });

    // ── Quick filters ──
    let filter = 'all';
    function applyFilter() {
        let shown = 0;
        rows.forEach(r => {
            const ok = filter === 'all'
                || (filter === 'claimed'   && r.dataset.claimed === '1')
                || (filter === 'unclaimed' && r.dataset.claimed === '0')
                || (filter === 'noprize'   && r.dataset.prize === '0');
            r.classList.toggle('hidden', !ok);
            if (!ok) { const cb = r.querySelector('.row-check'); if (cb) cb.checked = false; }
            shown += ok ? 1 : 0;
        });
        document.getElementById('filter-empty').classList.toggle('hidden', shown > 0 || rows.length === 0);
        syncBulk();
    }
    document.querySelectorAll('.winner-filter').forEach(b => b.addEventListener('click', () => {
        filter = b.dataset.filter;
        document.querySelectorAll('.winner-filter').forEach(x => {
            const on = x === b;
            x.classList.toggle('bg-primary', on); x.classList.toggle('text-white', on); x.classList.toggle('border-primary', on);
            x.classList.toggle('border-gray-300', !on); x.classList.toggle('text-gray-600', !on);
        });
        applyFilter();
    }));

    // ── Selection + bulk bar ──
    const selectAll = document.getElementById('select-all');
    const checked   = () => [...document.querySelectorAll('.row-check:checked')].map(c => c.value);
    function syncBulk() {
        const bar = document.getElementById('bulk-bar');
        if (!bar) return;
        const ids = checked();
        bar.classList.toggle('hidden', ids.length === 0);
        document.getElementById('bulk-count').textContent = ids.length;
        const visible = rows.filter(r => !r.classList.contains('hidden')).map(r => r.querySelector('.row-check'));
        if (selectAll) selectAll.checked = visible.length > 0 && visible.every(c => c.checked);
    }
    document.querySelectorAll('.row-check').forEach(c => c.addEventListener('change', syncBulk));
    selectAll?.addEventListener('change', () => {
        rows.filter(r => !r.classList.contains('hidden')).forEach(r => r.querySelector('.row-check').checked = selectAll.checked);
        syncBulk();
    });
    window.clearSelection = () => { document.querySelectorAll('.row-check').forEach(c => c.checked = false); syncBulk(); };

    window.bulkAction = async function (action) {
        const ids   = checked();
        const prize = document.getElementById('bulk-prize').value.trim();
        if (action === 'prize' && !prize) {
            Swal.fire({ icon: 'warning', title: 'Type the prize', text: 'Enter the prize to set for the selected winners, e.g. "5kg Bigas".', didOpen: swalBtn('#727cf5') });
            return;
        }
        const what = { prize: `set the prize to “${prize}” for`, claim: 'mark as CLAIMED', unclaim: 'mark as UNCLAIMED' }[action];
        const ok = await Swal.fire({
            icon: 'question', title: 'Apply to ' + ids.length + ' winner(s)?', text: `This will ${what} the selected winner(s).`,
            showCancelButton: true, confirmButtonText: 'Yes, apply', reverseButtons: true,
            didOpen: swalBtn(action === 'unclaim' ? '#ffbc00' : action === 'claim' ? '#0acf97' : '#727cf5'),
        });
        if (!ok.isConfirmed) return;

        const res = await fetch(BULK_URL, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ ids, action, prize_label: prize }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) {
            const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Something went wrong.');
            Swal.fire({ icon: 'error', title: 'Not saved', text: msg, didOpen: swalBtn('#fa5c7c') });
            return;
        }
        toast('success', data.message);
        setTimeout(() => location.reload(), 700);   // refresh stats + badges
    };

    // ── Inline prize edit: saves on Enter or when leaving the field ──
    async function savePrize(input) {
        const value = input.value.trim();
        if (value === (input.dataset.saved || '')) return;
        const state = input.parentElement.querySelector('.prize-state');
        state.className = 'prize-state mgc_loading_3_line animate-spin text-gray-400 text-base shrink-0';
        const res = await fetch(input.dataset.url, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ prize_label: value }),
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.success) {
            input.dataset.saved = value;
            input.closest('.winner-row').dataset.prize = value ? '1' : '0';
            state.className = 'prize-state mgc_check_circle_line text-success text-base shrink-0';
            setTimeout(() => state.className = 'prize-state text-base shrink-0', 1500);
        } else {
            state.className = 'prize-state mgc_alert_line text-danger text-base shrink-0';
            toast('error', 'Prize not saved');
        }
    }
    document.querySelectorAll('.prize-input').forEach(i => {
        i.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); i.blur(); } if (e.key === 'Escape') { i.value = i.dataset.saved || ''; i.blur(); } });
        i.addEventListener('blur', () => savePrize(i));
    });
})();
</script>
@endpush
