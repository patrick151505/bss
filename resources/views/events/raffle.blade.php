@extends('layouts.vertical', [
    'title'         => 'Raffle — ' . $event->title,
    'sub_title'     => $event->title,
    'sub_title_url' => route('events.show', $event),
    'tagline'       => 'Lucky draw',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 items-start">

    {{-- ── LEFT: Audience PIN card (full height) ── --}}
    <div class="xl:col-span-1 space-y-5">

        {{-- Pool stat --}}
        <div class="card">
            <div class="flex items-center gap-4 px-5 py-4">
                <span class="w-12 h-12 rounded-2xl bg-warning/10 text-warning flex items-center justify-center shrink-0 text-2xl">
                    <i class="mgc_group_line"></i>
                </span>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Eligible Pool</p>
                    <p id="pool-count" class="text-lg font-bold text-gray-800 dark:text-gray-100 leading-tight">Loading…</p>
                </div>
            </div>
        </div>

        {{-- Audience Live View PIN card --}}
        <div class="card border border-primary/20">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <i class="mgc_tv_2_line text-primary text-base"></i>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Audience Live View</p>
            </div>
            <div class="p-5 space-y-4">
                <p class="text-xs text-gray-500 leading-relaxed">
                    Generate a PIN so staff can open the audience draw page on any device — no login needed.
                </p>

                {{-- PIN display block --}}
                <div id="pin-area" class="{{ $event->hasValidPin() ? '' : 'hidden' }} space-y-3">
                    <div class="rounded-xl bg-primary/5 border border-primary/15 px-4 py-3 flex items-center gap-3">
                        <span class="font-mono text-3xl font-black tracking-[0.25em] text-primary flex-1 leading-none" id="pin-display">
                            {{ $event->raffle_pin ?? '' }}
                        </span>
                        <button onclick="copyPin()"
                            class="btn btn-sm bg-primary/10 text-primary hover:bg-primary hover:text-white shrink-0"
                            title="Copy PIN">
                            <i class="mgc_copy_line"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400" id="pin-expires">
                        @if($event->hasValidPin())
                            Expires {{ $event->raffle_pin_expires_at->format('M d, g:i A') }}
                        @endif
                    </p>

                    {{-- Shareable URL --}}
                    <div class="flex gap-2 items-center">
                        <input id="share-url" type="text" readonly
                            class="form-input text-xs flex-1 bg-gray-50 dark:bg-gray-800 text-gray-500 cursor-pointer"
                            value="{{ $event->hasValidPin() ? route('events.raffle.public', ['event' => $event->id, 'pin' => $event->raffle_pin]) : '' }}"
                            onclick="this.select()">
                        <button onclick="copyLink()"
                            class="btn btn-sm bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-200 hover:bg-primary hover:text-white shrink-0"
                            title="Copy link">
                            <i class="mgc_link_line"></i>
                        </button>
                    </div>
                </div>

                <div id="no-pin-area" class="{{ $event->hasValidPin() ? 'hidden' : '' }} py-4 text-center">
                    <i class="mgc_lock_line text-3xl text-gray-200 dark:text-gray-600 mb-2 block"></i>
                    <p class="text-xs text-gray-400">No active PIN yet.</p>
                </div>

                <button onclick="generatePin()"
                    class="btn bg-primary text-white w-full gap-2 justify-center">
                    <i class="mgc_refresh_1_line"></i>
                    <span id="gen-btn-label">{{ $event->hasValidPin() ? 'Regenerate PIN' : 'Generate PIN' }}</span>
                </button>

                <a href="{{ route('events.show', $event) }}"
                    class="btn bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-200 hover:bg-gray-200 w-full gap-2 justify-center text-sm">
                    <i class="mgc_left_line"></i> Back to Event
                </a>
            </div>
        </div>

    </div>

    {{-- ── RIGHT: Latest winner + History (2 cols) ── --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- Latest winner panel --}}
        <div id="winner-announcement" class="hidden card border-2 border-warning/30">
            <div class="px-5 py-3 border-b border-warning/10 flex items-center gap-2 bg-warning/5">
                <i class="mgc_star_fill text-warning text-base"></i>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Latest Winner</p>
            </div>
            <div class="p-6 flex items-center gap-5">
                <div class="relative shrink-0">
                    <div class="w-20 h-20 rounded-full overflow-hidden bg-warning/10 border-4 border-warning flex items-center justify-center text-2xl font-bold text-warning">
                        <img id="winner-photo" src="" class="w-full h-full object-cover hidden">
                        <span id="winner-initials"></span>
                    </div>
                    <span class="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-warning text-white flex items-center justify-center text-sm shadow">🎉</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-warning uppercase tracking-widest mb-1">Winner!</p>
                    <p id="winner-name" class="text-xl font-bold text-gray-800 dark:text-gray-100 truncate"></p>
                    <p id="winner-prize" class="text-sm text-warning font-semibold mt-0.5"></p>
                    <p id="winner-round" class="text-xs text-gray-400 mt-1"></p>
                </div>
            </div>
        </div>

        <div id="no-winner-yet" class="card">
            <div class="py-14 flex flex-col items-center gap-3 text-center">
                <i class="mgc_tv_2_line text-5xl text-gray-200 dark:text-gray-700"></i>
                <p class="text-sm font-semibold text-gray-400">No draws yet</p>
                <p class="text-xs text-gray-300 dark:text-gray-600 max-w-xs">
                    Open the audience page using the PIN on the left, then start the draw from there.
                </p>
            </div>
        </div>

        {{-- Winner history --}}
        <div class="card">
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Winner History</p>
                <button onclick="loadWinners()" class="text-xs text-primary hover:underline flex items-center gap-1">
                    <i class="mgc_refresh_1_line text-xs"></i> Refresh
                </button>
            </div>
            <div id="winner-list" class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[55vh] overflow-y-auto">
                <div class="py-10 text-center text-gray-400 text-sm animate-pulse">Loading…</div>
            </div>
        </div>

    </div>

</div>

@endsection

@push('inline-scripts')
<script>
const ROUTES = {
    rafflePool:    '{{ route('events.raffle.pool', $event) }}',
    raffleWinners: '{{ route('events.raffle.winners', $event) }}',
    generatePin:   '{{ route('events.raffle.generate-pin', $event) }}',
};
const CSRF = '{{ csrf_token() }}';

// ─── Init ─────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    loadRafflePool();
    loadWinners();
    setInterval(loadWinners, 5000);
    setInterval(loadRafflePool, 10000);
});

// ─── Pool ─────────────────────────────────────────────────────────────────────

async function loadRafflePool() {
    const res = await fetch(ROUTES.rafflePool).then(r => r.json());
    const count = res.success ? res.pool.length : 0;
    document.getElementById('pool-count').textContent = count > 0
        ? `${count} eligible attendee${count !== 1 ? 's' : ''}`
        : 'No eligible attendees';
}

// ─── Winner History ───────────────────────────────────────────────────────────

async function loadWinners() {
    const res = await fetch(ROUTES.raffleWinners).then(r => r.json());
    const el  = document.getElementById('winner-list');

    if (!res.success || !res.winners || res.winners.length === 0) {
        el.innerHTML = '<div class="py-10 text-center text-gray-400 text-sm">No draws yet.</div>';
        return;
    }

    // Show latest winner card
    const latest = res.winners[0];
    const photo  = document.getElementById('winner-photo');
    const initEl = document.getElementById('winner-initials');
    if (latest.photo) {
        photo.src = latest.photo;
        photo.classList.remove('hidden');
        initEl.textContent = '';
    } else {
        photo.classList.add('hidden');
        initEl.textContent = initials(latest.name);
    }
    document.getElementById('winner-name').textContent  = latest.name;
    document.getElementById('winner-prize').textContent = latest.prize_label ? '🏆 ' + latest.prize_label : '';
    document.getElementById('winner-round').textContent = `Round #${latest.round} · ${latest.drawn_at}`;
    document.getElementById('winner-announcement').classList.remove('hidden');
    document.getElementById('no-winner-yet').classList.add('hidden');

    // History rows
    el.innerHTML = res.winners.map(w => `
        <div class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <div class="w-10 h-10 rounded-full overflow-hidden bg-warning/10 border border-warning/20 flex items-center justify-center shrink-0 text-sm font-bold text-warning">
                ${w.photo ? `<img src="${w.photo}" class="w-full h-full object-cover">` : initials(w.name)}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">${w.name}</p>
                <p class="text-xs text-gray-400">${w.prize_label
                    ? `<span class="text-warning font-medium">${w.prize_label}</span> · ` : ''}${w.drawn_at}</p>
            </div>
            <span class="w-9 h-9 rounded-full bg-warning/10 text-warning text-xs font-bold flex items-center justify-center shrink-0 border border-warning/20">
                #${w.round}
            </span>
        </div>
    `).join('');
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function initials(name) {
    if (!name) return '?';
    return name.split(' ').map(w => w[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
}

// ─── PIN ──────────────────────────────────────────────────────────────────────

async function generatePin() {
    const btn = event.currentTarget;
    btn.disabled = true;
    btn.innerHTML = '<i class="mgc_loading_3_line animate-spin"></i> Generating…';

    const res = await fetch(ROUTES.generatePin, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
    }).then(r => r.json()).catch(() => ({ success: false, message: 'Network error' }));

    btn.disabled = false;
    btn.innerHTML = '<i class="mgc_refresh_1_line"></i> <span id="gen-btn-label">Regenerate PIN</span>';

    if (!res.success) {
        Swal.fire({ icon: 'error', title: 'Error', text: res.message, confirmButtonColor: '#fa5c7c' });
        return;
    }

    document.getElementById('pin-display').textContent = res.pin;
    document.getElementById('pin-expires').textContent = 'Expires ' + res.expires_at;
    document.getElementById('share-url').value = res.public_url;
    document.getElementById('pin-area').classList.remove('hidden');
    document.getElementById('no-pin-area').classList.add('hidden');

    Swal.fire({
        icon: 'success',
        title: 'PIN Generated',
        html: `Share this PIN with staff:<br>
               <span class="font-mono text-3xl font-black tracking-widest text-primary">${res.pin}</span><br>
               <small class="text-gray-400">Valid for 8 hours</small>`,
        confirmButtonColor: '#4361ee',
    });
}

function copyPin() {
    const pin = document.getElementById('pin-display').textContent.trim();
    navigator.clipboard.writeText(pin).then(() =>
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'PIN copied!', showConfirmButton: false, timer: 1500 })
    );
}

function copyLink() {
    const url = document.getElementById('share-url').value;
    navigator.clipboard.writeText(url).then(() =>
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Link copied!', showConfirmButton: false, timer: 1500 })
    );
}

window.loadWinners = loadWinners;
window.generatePin = generatePin;
window.copyPin     = copyPin;
window.copyLink    = copyLink;
</script>
@endpush
