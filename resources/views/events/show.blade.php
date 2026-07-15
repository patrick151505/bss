@extends('layouts.vertical', [
    'title'         => $event->title,
    'sub_title'     => 'Events',
    'sub_title_url' => route('events.index'),
    'tagline'       => 'Attendance & Raffle Management',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

{{-- ── Event header ── --}}
<div class="card mb-5 overflow-hidden">
    @if($event->cover_photo_url)
        <div class="w-full h-40 overflow-hidden">
            <img src="{{ $event->cover_photo_url }}" class="w-full h-full object-cover">
        </div>
    @endif
    <div class="p-5 flex flex-wrap items-start justify-between gap-4">
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-1">
                <span id="status-badge" class="inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2.5 py-1
                    {{ $event->effective_status === 'open' ? 'bg-success/15 text-success' : 'bg-gray-100 dark:bg-gray-700 text-gray-500' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $event->effective_status === 'open' ? 'bg-success' : 'bg-gray-400' }} inline-block"></span>
                    {{ ucfirst($event->effective_status) }}
                </span>
                @if($event->category)
                    <span class="text-xs font-medium text-primary bg-primary/10 rounded-full px-2.5 py-1">{{ $event->category }}</span>
                @endif
                @if($event->raffle_enabled)
                    <span class="text-xs font-semibold text-warning bg-warning/10 rounded-full px-2.5 py-1">
                        <i class="mgc_star_line"></i> Raffle Enabled
                    </span>
                @endif
            </div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 truncate">{{ $event->title }}</h2>
            @if($event->description)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $event->description }}</p>
            @endif
            <div class="flex flex-wrap gap-4 mt-2 text-xs text-gray-400">
                <span class="flex items-center gap-1">
                    <i class="mgc_calendar_line"></i>
                    {{ $event->event_date->format('M d, Y') }}
                    @if($event->event_start)
                        · {{ \Carbon\Carbon::parse($event->event_start)->format('g:i A') }}
                        @if($event->event_end)
                            – {{ \Carbon\Carbon::parse($event->event_end)->format('g:i A') }}
                        @endif
                    @endif
                </span>
                @if($event->venue)
                    <span class="flex items-center gap-1"><i class="mgc_location_line"></i> {{ $event->venue }}</span>
                @endif
                <span class="flex items-center gap-1"><i class="mgc_user_3_line"></i> <span id="header-count">...</span> attended</span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            @if($event->raffle_enabled)
            <a href="{{ route('events.raffle.page', $event) }}"
                class="btn bg-warning text-white text-sm gap-1.5 shadow-sm">
                <i class="mgc_star_line"></i> Open Raffle
            </a>
            @endif
            <button id="btn-toggle-status" onclick="toggleStatus()"
                class="btn bg-dark/25 text-slate-900 hover:bg-dark hover:text-white text-sm gap-1.5">
                <i class="mgc_power_line"></i>
                <span id="toggle-label">{{ $event->effective_status === 'open' ? 'Close Event' : 'Reopen Event' }}</span>
            </button>
            <a href="{{ route('events.edit', $event) }}"
                class="btn bg-warning/25 text-warning hover:bg-warning hover:text-white text-sm gap-1.5">
                <i class="mgc_edit_line"></i> Edit
            </a>
            <button onclick="deleteEvent()"
                class="btn bg-danger/25 text-danger hover:bg-danger hover:text-white text-sm">
                <i class="mgc_delete_line"></i>
            </button>
        </div>
    </div>
</div>

{{-- ── Attendance panel (full width) ── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- QR + Manual (left col) --}}
    <div class="space-y-5">

        {{-- QR Check-in --}}
        <div class="card">
            <div class="flex items-center gap-3 px-5 py-3 bg-primary/5 border-b border-primary/10">
                <span class="w-6 h-6 rounded-full bg-primary text-white text-xs flex items-center justify-center shrink-0">
                    <i class="mgc_scan_line text-xs"></i>
                </span>
                <span class="text-sm font-semibold text-primary uppercase tracking-wide">QR Check-in</span>
            </div>
            <div class="p-5">
                <div class="flex gap-3">
                    <input type="text" id="qr-input" class="form-input flex-1"
                        placeholder="Scan or type QR code…" autocomplete="off">
                    <button onclick="doQrCheckin()" class="btn bg-primary text-white gap-1.5 text-sm shrink-0">
                        <i class="mgc_check_line"></i> Check In
                    </button>
                </div>
                <div id="qr-feedback" class="hidden mt-3 p-3 rounded-xl text-sm font-medium"></div>
            </div>
        </div>

        {{-- Manual citizen search check-in --}}
        <div class="card">
            <div class="flex items-center gap-3 px-5 py-3 bg-info/5 border-b border-info/10">
                <span class="w-6 h-6 rounded-full bg-info text-white text-xs flex items-center justify-center shrink-0">
                    <i class="mgc_user_add_line text-xs"></i>
                </span>
                <span class="text-sm font-semibold text-info uppercase tracking-wide">Manual Check-in</span>
            </div>
            <div class="p-5">
                <div class="flex gap-3 mb-3">
                    <div class="relative flex-1">
                        <i class="mgc_search_line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" id="manual-search" class="form-input pl-8 w-full"
                            placeholder="Search citizen by name…">
                    </div>
                    <button onclick="searchManual(1)" class="btn bg-info text-white text-sm gap-1.5 shrink-0">
                        <i class="mgc_search_line"></i> Search
                    </button>
                </div>
                <div id="manual-results"></div>
                <div id="manual-pagination" class="hidden flex justify-center gap-1 mt-3 flex-wrap"></div>
            </div>
        </div>

        {{-- Bulk Add trigger --}}
        <button onclick="openBulkWizard()"
            class="w-full card p-4 flex items-center gap-3 hover:bg-success/5 transition-colors text-left group">
            <span class="w-10 h-10 rounded-xl bg-success/10 group-hover:bg-success/20 flex items-center justify-center shrink-0 transition-colors">
                <i class="mgc_group_line text-success text-lg"></i>
            </span>
            <div>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Bulk Add Attendance</p>
                <p class="text-xs text-gray-400 mt-0.5">Filter citizens and add them all at once</p>
            </div>
            <i class="mgc_right_line text-gray-300 ml-auto text-sm"></i>
        </button>

    </div>

    {{-- Attendance Log (right 2 cols) --}}
    <div class="lg:col-span-2">
        <div class="card h-full flex flex-col">
            <div class="flex items-center gap-3 px-5 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600 shrink-0">
                <i class="mgc_list_check_3_line text-gray-500"></i>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide flex-1">
                    Attendance Log
                </span>
                <div class="relative">
                    <i class="mgc_search_line absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" id="att-search" class="form-input pl-7 text-xs py-1.5 w-44"
                        placeholder="Search…" oninput="loadAttendance(1)">
                </div>
            </div>
            <div id="attendance-list" class="divide-y divide-gray-100 dark:divide-gray-700 flex-1"></div>
            <div id="attendance-pagination" class="hidden items-center justify-between px-5 py-3 border-t border-gray-100 dark:border-gray-700 shrink-0">
                <span class="text-xs text-gray-400" id="att-pag-info"></span>
                <div class="flex gap-1 flex-wrap" id="att-pag-btns"></div>
            </div>
        </div>
    </div>

</div>

{{-- ── Toast ── --}}
<div id="manual-toast" class="hidden fixed bottom-6 right-6 z-50 flex items-center gap-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl px-5 py-3 text-sm font-medium max-w-xs">
    <div id="manual-toast-icon" class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"></div>
    <span id="manual-toast-msg"></span>
</div>


{{-- ══════════════════════════════════════════════════════════════════════════
     BULK ADD WIZARD MODAL (3 steps)
══════════════════════════════════════════════════════════════════════════ --}}
<div id="bulk-modal" class="hidden fixed inset-0 z-[998] flex items-center justify-center p-4"
    style="background:rgba(0,0,0,0.6);backdrop-filter:blur(3px)">
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">

        {{-- Modal header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700 shrink-0">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-success text-white flex items-center justify-center">
                    <i class="mgc_group_line text-sm"></i>
                </span>
                <p class="font-bold text-gray-800 dark:text-gray-100">Bulk Add Attendance</p>
            </div>
            <button onclick="closeBulkWizard()"
                class="w-8 h-8 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 hover:bg-gray-200 transition-colors">
                <i class="mgc_close_line text-sm"></i>
            </button>
        </div>

        {{-- Step indicator --}}
        <div class="flex items-center gap-0 px-6 py-4 border-b border-gray-100 dark:border-gray-700 shrink-0">
            <div class="step-dot flex items-center gap-2 flex-1" id="step-indicator-1">
                <span class="w-7 h-7 rounded-full bg-success text-white text-xs font-bold flex items-center justify-center shrink-0">1</span>
                <span class="text-xs font-medium text-success">Filters</span>
            </div>
            <div class="h-px bg-gray-200 dark:bg-gray-600 flex-1 mx-2"></div>
            <div class="step-dot flex items-center gap-2 flex-1" id="step-indicator-2">
                <span id="step2-num" class="w-7 h-7 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-400 text-xs font-bold flex items-center justify-center shrink-0">2</span>
                <span id="step2-label" class="text-xs font-medium text-gray-400">Preview</span>
            </div>
            <div class="h-px bg-gray-200 dark:bg-gray-600 flex-1 mx-2"></div>
            <div class="step-dot flex items-center gap-2" id="step-indicator-3">
                <span id="step3-num" class="w-7 h-7 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-400 text-xs font-bold flex items-center justify-center shrink-0">3</span>
                <span id="step3-label" class="text-xs font-medium text-gray-400">Done</span>
            </div>
        </div>

        {{-- Step bodies --}}
        <div class="flex-1 overflow-y-auto">

            {{-- STEP 1: Filters --}}
            <div id="bulk-step-1" class="p-6 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-500 font-medium block mb-1.5">Zone / Address</label>
                        <select id="bulk-address" class="form-select w-full">
                            <option value="">All zones</option>
                            @foreach($addresses as $addr)
                                <option value="{{ $addr->id }}">{{ $addr->description }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 font-medium block mb-1.5">Gender</label>
                        <select id="bulk-gender" class="form-select w-full">
                            <option value="">Any</option>
                            <option value="1">Male</option>
                            <option value="2">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 font-medium block mb-1.5">Min Age</label>
                        <input type="number" id="bulk-min-age" class="form-input w-full" placeholder="e.g. 18" min="0" max="150">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 font-medium block mb-1.5">Max Age</label>
                        <input type="number" id="bulk-max-age" class="form-input w-full" placeholder="e.g. 60" min="0" max="150">
                    </div>
                </div>

                <div>
                    <p class="text-xs text-gray-500 font-medium mb-2">Special Groups</p>
                    <div class="flex flex-wrap gap-3">
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-gray-700 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-success transition-colors">
                            <input type="checkbox" id="bulk-voters" class="form-checkbox"> Voters
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-gray-700 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-success transition-colors">
                            <input type="checkbox" id="bulk-birthday" class="form-checkbox"> Birthday this month
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-gray-700 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-success transition-colors">
                            <input type="checkbox" id="bulk-senior" class="form-checkbox"> Senior (60+)
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-gray-700 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-success transition-colors">
                            <input type="checkbox" id="bulk-pwd" class="form-checkbox"> PWD
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-gray-700 px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-success transition-colors">
                            <input type="checkbox" id="bulk-solo" class="form-checkbox"> Solo Parent
                        </label>
                    </div>
                </div>

                <div id="bulk-filter-info" class="hidden p-3 rounded-xl bg-gray-50 dark:bg-gray-700 text-xs text-gray-500"></div>
            </div>

            {{-- STEP 2: Preview --}}
            <div id="bulk-step-2" class="hidden p-6 space-y-4">
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="p-4 rounded-2xl bg-success/10 border border-success/20">
                        <p id="bulk-count-new" class="text-3xl font-bold text-success">0</p>
                        <p class="text-xs text-gray-400 mt-1">To Add</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
                        <p id="bulk-count-already" class="text-3xl font-bold text-gray-400">0</p>
                        <p class="text-xs text-gray-400 mt-1">Already In</p>
                    </div>
                    <div class="p-4 rounded-2xl bg-primary/10 border border-primary/20">
                        <p id="bulk-count-total" class="text-3xl font-bold text-primary">0</p>
                        <p class="text-xs text-gray-400 mt-1">Matched</p>
                    </div>
                </div>
                <div class="border border-gray-200 dark:border-gray-600 rounded-2xl overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">Citizens</p>
                        <p class="text-xs text-gray-400"><span id="bulk-preview-showing">0</span> shown</p>
                    </div>
                    <div id="bulk-preview-list" class="max-h-64 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700"></div>
                </div>
                <p id="bulk-zero-msg" class="hidden text-center text-sm text-gray-400 py-4">All matched citizens are already checked in.</p>
            </div>

            {{-- STEP 3: Done --}}
            <div id="bulk-step-3" class="hidden p-10 text-center">
                <div class="w-20 h-20 rounded-full bg-success/10 flex items-center justify-center mx-auto mb-4">
                    <i class="mgc_check_circle_line text-success text-4xl"></i>
                </div>
                <p class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-1">Done!</p>
                <p class="text-sm text-gray-400 mb-1"><span id="done-added" class="font-bold text-success">0</span> citizens added to attendance.</p>
                <p class="text-sm text-gray-400"><span id="done-skipped" class="font-bold text-gray-400">0</span> were already checked in.</p>
            </div>

        </div>

        {{-- Modal footer / actions --}}
        <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-gray-100 dark:border-gray-700 shrink-0">
            <button id="bulk-back-btn" onclick="bulkWizardBack()" class="hidden btn bg-dark/25 text-slate-900 hover:bg-dark hover:text-white gap-1.5">
                <i class="mgc_left_line"></i> Back
            </button>
            <div class="ml-auto flex gap-3">
                <button id="bulk-cancel-btn" onclick="closeBulkWizard()" class="btn bg-dark/25 text-slate-900 hover:bg-dark hover:text-white">
                    Cancel
                </button>
                <button id="bulk-next-btn" onclick="bulkWizardNext()"
                    class="btn bg-success text-white gap-1.5">
                    <i class="mgc_eye_line"></i> Preview
                </button>
            </div>
        </div>

    </div>
</div>

@endsection

@push('inline-scripts')
<script>
const ROUTES = {
    attendance:     '{{ route('events.attendance.list', $event) }}',
    checkinQr:      '{{ route('events.checkin.qr', $event) }}',
    checkinManual:  '{{ route('events.checkin.manual', $event) }}',
    attRemove:      '{{ route('events.attendance.remove', $event) }}',
    citizenSearch:  '{{ route('events.citizens.search', $event) }}',
    bulkPreviewUrl: '{{ route('events.bulk.preview', $event) }}',
    bulkAddUrl:     '{{ route('events.bulk.add', $event) }}',
    toggleStatus:   '{{ route('events.toggle-status', $event) }}',
    destroy:        '{{ route('events.destroy', $event) }}',
};
const CSRF = '{{ csrf_token() }}';

let manualPage  = 1;
let attPage     = 1;
let bulkStep    = 1;
let bulkPreviewData = null;

// ─── Init ─────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    loadAttendance(1);
    document.getElementById('qr-input').addEventListener('keydown', e => {
        if (e.key === 'Enter') doQrCheckin();
    });
    document.getElementById('manual-search').addEventListener('keydown', e => {
        if (e.key === 'Enter') searchManual(1);
    });
});

// ─── Toggle Status ────────────────────────────────────────────────────────────

async function toggleStatus() {
    const confirmed = await Swal.fire({
        title: 'Toggle event status?', icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4361ee', cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, toggle it',
    });
    if (!confirmed.isConfirmed) return;

    const res = await fetch(ROUTES.toggleStatus, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
    }).then(r => r.json());

    if (res.success) {
        const isOpen = res.status === 'open';
        const badge  = document.getElementById('status-badge');
        badge.className = `inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2.5 py-1 ${
            isOpen ? 'bg-success/15 text-success' : 'bg-gray-100 dark:bg-gray-700 text-gray-500'}`;
        badge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full ${isOpen ? 'bg-success' : 'bg-gray-400'} inline-block"></span>${isOpen ? 'Open' : 'Closed'}`;
        document.getElementById('toggle-label').textContent = isOpen ? 'Close Event' : 'Reopen Event';
    }
}

// ─── Delete Event ─────────────────────────────────────────────────────────────

async function deleteEvent() {
    const confirmed = await Swal.fire({
        title: 'Delete this event?',
        text: 'All attendance and winner records will be permanently removed.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#fa5c7c', cancelButtonColor: '#6c757d',
        confirmButtonText: 'Delete',
    });
    if (!confirmed.isConfirmed) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = ROUTES.destroy;
    form.innerHTML = `<input name="_token" value="${CSRF}"><input name="_method" value="DELETE">`;
    document.body.appendChild(form);
    form.submit();
}

// ─── QR Check-in ─────────────────────────────────────────────────────────────

async function doQrCheckin() {
    const input  = document.getElementById('qr-input');
    const qrcode = input.value.trim();
    if (!qrcode) return;

    const res = await fetch(ROUTES.checkinQr, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify({ qrcode }),
    }).then(r => r.json());

    const fb = document.getElementById('qr-feedback');
    fb.className = `mt-3 p-3 rounded-xl text-sm font-medium ${res.success ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'}`;
    fb.textContent = res.success ? `✓ ${res.name} checked in!` : `✗ ${res.message}`;
    fb.classList.remove('hidden');

    if (res.success) {
        input.value = '';
        loadAttendance(attPage);
        updateHeaderCount();
    }

    setTimeout(() => fb.classList.add('hidden'), 4000);
    input.focus();
}

// ─── Manual Citizen Search ────────────────────────────────────────────────────

async function searchManual(page = 1) {
    manualPage = page;
    const search = document.getElementById('manual-search').value;
    const params = new URLSearchParams({ search, page, limit: 8 });
    const res    = await fetch(ROUTES.citizenSearch + '?' + params).then(r => r.json());
    const el     = document.getElementById('manual-results');

    if (!res.success || !res.data || res.data.length === 0) {
        el.innerHTML = '<p class="text-center text-sm text-gray-400 py-4">No citizens found.</p>';
        document.getElementById('manual-pagination').classList.add('hidden');
        return;
    }

    el.innerHTML = res.data.map(c => `
        <div class="flex items-center gap-3 py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
            <div class="w-8 h-8 rounded-full overflow-hidden bg-gray-200 shrink-0">
                ${c.photo
                    ? `<img src="${c.photo}" class="w-full h-full object-cover">`
                    : `<div class="w-full h-full flex items-center justify-center text-xs font-bold text-gray-500">${initials(c.name)}</div>`}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">${c.name}</p>
                <p class="text-xs text-gray-400">${c.age ? c.age + ' y/o · ' : ''}${c.gender}${c.zone ? ' · ' + c.zone : ''}</p>
            </div>
            ${c.already_in
                ? '<span class="text-xs text-success font-medium shrink-0">✓ In</span>'
                : `<button onclick="manualCheckin(${c.id}, '${escHtml(c.name)}')"
                    class="btn bg-info/20 text-info hover:bg-info hover:text-white text-xs py-1 px-2.5 shrink-0">+ Add</button>`}
        </div>
    `).join('');

    const pagEl = document.getElementById('manual-pagination');
    const totalPages = Math.ceil(res.total / res.limit);
    if (totalPages <= 1) { pagEl.classList.add('hidden'); return; }
    pagEl.classList.remove('hidden');
    pagEl.innerHTML = buildPager(totalPages, page, 'searchManual');
}

async function manualCheckin(citizenId, name) {
    const res = await fetch(ROUTES.checkinManual, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify({ citizenId }),
    }).then(r => r.json());

    showToast(res.success, res.success ? `${name} checked in!` : res.message);
    if (res.success) {
        loadAttendance(attPage);
        searchManual(manualPage);
        updateHeaderCount();
    }
}

// ─── Attendance List ──────────────────────────────────────────────────────────

async function loadAttendance(page = 1) {
    attPage = page;
    const search = document.getElementById('att-search').value;
    const params = new URLSearchParams({ page, search });
    const el     = document.getElementById('attendance-list');

    el.innerHTML = '<div class="p-6 text-center text-gray-400 text-sm animate-pulse">Loading…</div>';

    const res = await fetch(ROUTES.attendance + '?' + params).then(r => r.json());

    if (!res.success || !res.data || res.data.length === 0) {
        el.innerHTML = '<div class="p-8 text-center text-gray-400 text-sm">No attendance records yet.</div>';
        document.getElementById('attendance-pagination').classList.add('hidden');
        return;
    }

    el.innerHTML = res.data.map(a => `
        <div class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group">
            <div class="w-9 h-9 rounded-full overflow-hidden bg-gray-200 shrink-0">
                ${a.photo
                    ? `<img src="${a.photo}" class="w-full h-full object-cover">`
                    : `<div class="w-full h-full flex items-center justify-center text-xs font-bold text-gray-500">${initials(a.citizen)}</div>`}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">${a.citizen}</p>
                <p class="text-xs text-gray-400">${a.time_in}${a.zone ? ' · ' + a.zone : ''}</p>
            </div>
            ${methodBadge(a.method)}
            <button onclick="removeAttendance(${a.id})"
                class="opacity-0 group-hover:opacity-100 transition-opacity btn bg-danger/20 text-danger hover:bg-danger hover:text-white text-xs py-1 px-2 shrink-0">
                <i class="mgc_close_line"></i>
            </button>
        </div>
    `).join('');

    updateHeaderCount(res.total);

    const totalPages = Math.ceil(res.total / res.limit);
    const pagEl = document.getElementById('attendance-pagination');
    if (totalPages <= 1) { pagEl.classList.add('hidden'); return; }
    pagEl.classList.remove('hidden');

    const from = (page - 1) * res.limit + 1;
    const to   = Math.min(page * res.limit, res.total);
    document.getElementById('att-pag-info').textContent = `${from}–${to} of ${res.total}`;
    document.getElementById('att-pag-btns').innerHTML = buildPager(totalPages, page, 'loadAttendance');
}

async function removeAttendance(id) {
    const confirmed = await Swal.fire({
        title: 'Remove attendance?', icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#fa5c7c', cancelButtonColor: '#6c757d', confirmButtonText: 'Remove',
    });
    if (!confirmed.isConfirmed) return;

    const res = await fetch(ROUTES.attRemove, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify({ attendanceId: id }),
    }).then(r => r.json());

    if (res.success) {
        loadAttendance(attPage);
    }
}

function updateHeaderCount(total) {
    if (total !== undefined) {
        document.getElementById('header-count').textContent = total;
        return;
    }
    fetch(ROUTES.attendance + '?page=1&limit=1').then(r => r.json()).then(res => {
        if (res.success) document.getElementById('header-count').textContent = res.total;
    });
}

// ─── Bulk Wizard ──────────────────────────────────────────────────────────────

function openBulkWizard() {
    bulkStep = 1;
    bulkPreviewData = null;
    showBulkStep(1);
    document.getElementById('bulk-modal').classList.remove('hidden');
}

function closeBulkWizard() {
    document.getElementById('bulk-modal').classList.add('hidden');
}

function showBulkStep(step) {
    bulkStep = step;
    [1, 2, 3].forEach(n => {
        document.getElementById(`bulk-step-${n}`).classList.toggle('hidden', n !== step);
    });

    // Step indicators
    const activate = (n, active) => {
        const num   = n === 1 ? document.querySelector('#step-indicator-1 span:first-child') : document.getElementById(`step${n}-num`);
        const label = n === 1 ? document.querySelector('#step-indicator-1 span:last-child')  : document.getElementById(`step${n}-label`);
        num.className   = `w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center shrink-0 ${active ? 'bg-success text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-400'}`;
        label.className = `text-xs font-medium ${active ? 'text-success' : 'text-gray-400'}`;
    };
    activate(1, step >= 1);
    activate(2, step >= 2);
    activate(3, step >= 3);

    // Footer buttons
    document.getElementById('bulk-back-btn').classList.toggle('hidden', step === 1);
    const nextBtn   = document.getElementById('bulk-next-btn');
    const cancelBtn = document.getElementById('bulk-cancel-btn');

    if (step === 1) {
        nextBtn.innerHTML = '<i class="mgc_eye_line"></i> Preview';
        nextBtn.classList.remove('hidden');
        cancelBtn.textContent = 'Cancel';
    } else if (step === 2) {
        const canAdd = bulkPreviewData && bulkPreviewData.new > 0;
        nextBtn.innerHTML = `<i class="mgc_user_add_line"></i> Add ${canAdd ? bulkPreviewData.new : 0} Citizens`;
        nextBtn.classList.toggle('hidden', !canAdd);
        cancelBtn.textContent = 'Close';
    } else {
        nextBtn.classList.add('hidden');
        cancelBtn.textContent = 'Close';
    }
}

async function bulkWizardNext() {
    if (bulkStep === 1) {
        await doBulkPreview();
    } else if (bulkStep === 2) {
        await doBulkAdd();
    }
}

function bulkWizardBack() {
    if (bulkStep === 2) showBulkStep(1);
}

function getBulkParams() {
    const p = new URLSearchParams();
    const address = document.getElementById('bulk-address').value;
    const gender  = document.getElementById('bulk-gender').value;
    const minAge  = document.getElementById('bulk-min-age').value;
    const maxAge  = document.getElementById('bulk-max-age').value;
    if (address) p.append('addressId', address);
    if (gender)  p.append('gender', gender);
    if (minAge)  p.append('min_age', minAge);
    if (maxAge)  p.append('max_age', maxAge);
    if (document.getElementById('bulk-voters').checked)  p.append('voters', '1');
    if (document.getElementById('bulk-birthday').checked) p.append('birthday_month', '1');
    if (document.getElementById('bulk-senior').checked)  p.append('senior', '1');
    if (document.getElementById('bulk-pwd').checked)     p.append('pwd', '1');
    if (document.getElementById('bulk-solo').checked)    p.append('soloparents', '1');
    return p;
}

async function doBulkPreview() {
    const nextBtn = document.getElementById('bulk-next-btn');
    nextBtn.disabled = true;
    nextBtn.innerHTML = '<i class="mgc_loading_3_line animate-spin"></i> Loading…';

    const res = await fetch(ROUTES.bulkPreviewUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/x-www-form-urlencoded' },
        body: getBulkParams().toString(),
    }).then(r => r.json());

    nextBtn.disabled = false;

    if (!res.success) {
        showToast(false, 'Error loading preview.');
        nextBtn.innerHTML = '<i class="mgc_eye_line"></i> Preview';
        return;
    }

    bulkPreviewData = res;
    const already = res.total - res.new;

    document.getElementById('bulk-count-new').textContent    = res.new;
    document.getElementById('bulk-count-already').textContent = already;
    document.getElementById('bulk-count-total').textContent  = res.total;
    document.getElementById('bulk-preview-showing').textContent = res.data.length;

    document.getElementById('bulk-preview-list').innerHTML = res.data.map(c => `
        <div class="flex items-center gap-3 px-4 py-2.5 ${c.already_in ? 'opacity-40' : ''}">
            <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs shrink-0 ${c.already_in ? 'bg-success/20 text-success' : 'bg-primary/10 text-primary'}">
                ${c.already_in ? '✓' : '+'}
            </span>
            <span class="flex-1 text-sm text-gray-800 dark:text-gray-100 truncate">${c.name}</span>
            <span class="text-xs text-gray-400 shrink-0">${c.age ? c.age + 'y' : ''} · ${c.gender[0]}</span>
        </div>
    `).join('');

    document.getElementById('bulk-zero-msg').classList.toggle('hidden', res.new > 0);
    showBulkStep(2);
}

async function doBulkAdd() {
    const nextBtn = document.getElementById('bulk-next-btn');
    nextBtn.disabled = true;
    nextBtn.innerHTML = '<i class="mgc_loading_3_line animate-spin"></i> Adding…';

    const res = await fetch(ROUTES.bulkAddUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/x-www-form-urlencoded' },
        body: getBulkParams().toString(),
    }).then(r => r.json());

    nextBtn.disabled = false;

    if (!res.success) {
        showToast(false, 'Error adding citizens.');
        nextBtn.innerHTML = `<i class="mgc_user_add_line"></i> Add ${bulkPreviewData?.new ?? ''} Citizens`;
        return;
    }

    document.getElementById('done-added').textContent   = res.added;
    document.getElementById('done-skipped').textContent = res.skipped;

    showBulkStep(3);
    loadAttendance(1);
    if (RAFFLE_ENABLED) loadRafflePool();
}


// ─── Pagination helper ────────────────────────────────────────────────────────

function buildPager(total, current, fn) {
    if (total <= 1) return '';
    const pages = [];
    // Always show first, last, current ±1, with ellipsis
    const show = new Set([1, total, current, current - 1, current + 1].filter(p => p >= 1 && p <= total));
    const sorted = [...show].sort((a, b) => a - b);

    let html = '';
    let prev = 0;
    for (const p of sorted) {
        if (prev && p - prev > 1) {
            html += `<span class="w-7 h-7 flex items-center justify-center text-xs text-gray-400">…</span>`;
        }
        html += `<button onclick="${fn}(${p})"
            class="w-7 h-7 rounded-lg text-xs font-medium ${p === current
                ? 'bg-primary text-white'
                : 'bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50'}">${p}</button>`;
        prev = p;
    }

    // Prev / Next arrows
    const prevBtn = current > 1
        ? `<button onclick="${fn}(${current - 1})" class="w-7 h-7 rounded-lg text-xs border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 flex items-center justify-center"><i class="mgc_left_line"></i></button>`
        : '';
    const nextBtn = current < total
        ? `<button onclick="${fn}(${current + 1})" class="w-7 h-7 rounded-lg text-xs border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 flex items-center justify-center"><i class="mgc_right_line"></i></button>`
        : '';

    return prevBtn + html + nextBtn;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function methodBadge(method) {
    const map = { qr: 'bg-primary/15 text-primary', manual: 'bg-warning/15 text-warning', bulk: 'bg-success/15 text-success' };
    return `<span class="text-xs font-semibold rounded-full px-2 py-0.5 ${map[method] || 'bg-gray-100 text-gray-500'}">${method}</span>`;
}

function initials(name) {
    if (!name) return '?';
    return name.split(' ').map(w => w[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
}

function escHtml(str) {
    return str.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

function showToast(success, msg) {
    const toast = document.getElementById('manual-toast');
    const icon  = document.getElementById('manual-toast-icon');
    icon.className = `w-8 h-8 rounded-full flex items-center justify-center shrink-0 ${success ? 'bg-success/20 text-success' : 'bg-danger/20 text-danger'}`;
    icon.innerHTML = success ? '<i class="mgc_check_line"></i>' : '<i class="mgc_close_line"></i>';
    document.getElementById('manual-toast-msg').textContent = msg;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 4000);
}

window.toggleStatus     = toggleStatus;
window.deleteEvent      = deleteEvent;
window.doQrCheckin      = doQrCheckin;
window.searchManual     = searchManual;
window.manualCheckin    = manualCheckin;
window.loadAttendance   = loadAttendance;
window.removeAttendance = removeAttendance;
window.openBulkWizard   = openBulkWizard;
window.closeBulkWizard  = closeBulkWizard;
window.bulkWizardNext   = bulkWizardNext;
window.bulkWizardBack   = bulkWizardBack;
</script>
@endpush
